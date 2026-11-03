<?php

use Apex\Locale\Exceptions\CurrencyMismatch;
use Apex\Locale\Money;

// ── construction ────────────────────────────────────────────────────────

it('holds an integer amount and an upper-cased ISO code', function () {
    $m = Money::of(1234, 'eur');

    expect($m->amount())->toBe(1234)->and($m->currency())->toBe('EUR');
});

it('refuses a code that is not three letters', function (string $code) {
    Money::of(1, $code);
})->with(['EU', 'EURO', '978', ''])->throws(InvalidArgumentException::class);

// ── arithmetic ──────────────────────────────────────────────────────────

it('adds, subtracts and multiplies exactly', function () {
    $a = Money::of(1050, 'EUR');

    expect($a->plus(Money::of(25, 'EUR'))->amount())->toBe(1075)
        ->and($a->minus(Money::of(2000, 'EUR'))->amount())->toBe(-950)
        ->and($a->times(3)->amount())->toBe(3150)
        ->and($a->negate()->amount())->toBe(-1050)
        ->and($a->negate()->abs()->amount())->toBe(1050);
});

it('refuses to mix currencies', function (string $method) {
    Money::of(100, 'EUR')->{$method}(Money::of(100, 'GBP'));
})->with(['plus', 'minus', 'compare', 'greaterThan', 'lessThan'])->throws(CurrencyMismatch::class);

it('multiplies by a ratio, rounding half away from zero', function (int $amount, int $num, int $den, int $expected) {
    expect(Money::of($amount, 'EUR')->multiply($num, $den)->amount())->toBe($expected);
})->with([
    [100, 1, 3, 33],     // 33.33 → 33
    [100, 2, 3, 67],     // 66.67 → 67
    [5, 1, 2, 3],        // 2.5 → 3 (away from zero)
    [-5, 1, 2, -3],      // -2.5 → -3 (away from zero)
    [1000, 15, 100, 150],
]);

it('refuses an overflow rather than losing precision', function () {
    Money::of(PHP_INT_MAX, 'EUR')->times(2);
})->throws(OverflowException::class);

// ── allocation ──────────────────────────────────────────────────────────

it('allocates without losing a cent', function (int $amount, array $ratios, array $expected) {
    $parts = Money::of($amount, 'EUR')->allocate($ratios);

    expect(array_map(fn (Money $m) => $m->amount(), $parts))->toBe($expected)
        ->and(array_sum(array_map(fn (Money $m) => $m->amount(), $parts)))->toBe($amount);
})->with([
    [100, [1, 1, 1], [34, 33, 33]],
    [1000, [1, 1], [500, 500]],
    [5, [3, 7], [2, 3]],
    [-100, [1, 1, 1], [-34, -33, -33]],
    [7, [0, 1, 1], [0, 4, 3]],
]);

it('refuses ratios that are empty, negative or all zero', function (array $ratios) {
    Money::of(100, 'EUR')->allocate($ratios);
})->with([[[]], [[0, 0]], [[1, -1]]])->throws(InvalidArgumentException::class);

// ── VAT ─────────────────────────────────────────────────────────────────

it('splits a VAT-inclusive gross into net and VAT', function (int $gross, int $bp, int $net, int $vat) {
    $split = Money::of($gross, 'EUR')->splitGross($bp);

    expect($split['net']->amount())->toBe($net)
        ->and($split['vat']->amount())->toBe($vat)
        ->and($split['net']->amount() + $split['vat']->amount())->toBe($gross);
})->with([
    [1180, 1800, 1000, 180],   // €11.80 at 18% → €10.00 + €1.80
    [1000, 1800, 847, 153],    // €10.00 at 18% → 152.54 → 153
    [107, 700, 100, 7],
    [500, 0, 500, 0],
    [-1180, 1800, -1000, -180], // a refund mirrors the sale
]);

/*
 * The agreement with TBX's OrderCalculator (LC1 D7): its VAT is
 * `round($gross - $gross / (1 + $rate / 100), 2)` on float euros. Every gross from -€1,000.00 to
 * €1,000.00 is compared.
 *
 * The two can differ ONLY where the exact VAT is a half cent (at 20 %: €0.03 → 0.5 cent). There
 * the float is noise — TBX gives 0 for 3 cents (0.004999…) but 2 for 9 cents (exactly 1.5) — and
 * no integer rule can copy noise. Money rounds every half away from zero. At Malta's rates
 * 18, 7, 5 and 0 an exact half cannot occur; at Malta's 12 % 7,142 can, and TBX's float happens to
 * round every one of them away from zero too — so across all of Malta's rates the agreement is total.
 */
function vatDisagreements(float $rate): array
{
    $bp = (int) round($rate * 100);
    $out = [];

    for ($cents = -100000; $cents <= 100000; $cents++) {
        $gross = $cents / 100;
        $tbxVat = (int) round(round($gross - $gross / (1 + $rate / 100), 2) * 100);

        if ($tbxVat !== Money::of($cents, 'EUR')->splitGross($bp)['vat']->amount()) {
            $out[] = $cents;
        }
    }

    return $out;
}

it('agrees with TBX\'s float VAT formula on every amount to €1,000', function (float $rate) {
    expect(vatDisagreements($rate))->toBe([]);
})->with([18.0, 12.0, 7.0, 5.0, 0.0, 21.0, 19.0, 19.5, 10.0]);

it('differs from TBX only at exact half cents, and rounds those away from zero', function () {
    $bp = 2000;
    $disagreements = vatDisagreements(20.0);

    expect($disagreements)->not->toBeEmpty();

    foreach ($disagreements as $cents) {
        // exact half: cents × bp / (10000 + bp) has a remainder of exactly half the divisor
        expect(abs($cents * $bp) % (10000 + $bp) * 2)->toBe(10000 + $bp, "{$cents} is not an exact half")
            ->and(abs(Money::of($cents, 'EUR')->splitGross($bp)['vat']->amount()))
            ->toBe(intdiv(abs($cents * $bp), 10000 + $bp) + 1);
    }
});

it('refuses a negative rate', function () {
    Money::of(100, 'EUR')->splitGross(-1);
})->throws(InvalidArgumentException::class);

// ── cash ────────────────────────────────────────────────────────────────

it('rounds to a cash step, half away from zero', function (int $amount, int $step, int $expected) {
    expect(Money::of($amount, 'EUR')->roundToStep($step)->amount())->toBe($expected);
})->with([
    [1232, 5, 1230],
    [1233, 5, 1235],
    [1237, 5, 1235],
    [1238, 5, 1240],
    [-1233, 5, -1235],
    [1234, 1, 1234],
    [149, 100, 100],
    [150, 100, 200],
]);

it('refuses a step below one unit', function () {
    Money::of(100, 'EUR')->roundToStep(0);
})->throws(InvalidArgumentException::class);

// ── comparison and display ──────────────────────────────────────────────

it('compares', function () {
    $ten = Money::of(1000, 'EUR');

    expect($ten->equals(Money::of(1000, 'EUR')))->toBeTrue()
        ->and($ten->equals(Money::of(1000, 'GBP')))->toBeFalse()
        ->and($ten->greaterThan(Money::of(999, 'EUR')))->toBeTrue()
        ->and($ten->lessThan(Money::of(999, 'EUR')))->toBeFalse()
        ->and(Money::zero('EUR')->isZero())->toBeTrue()
        ->and(Money::of(-1, 'EUR')->isNegative())->toBeTrue();
});

it('writes a plain decimal in the minor unit', function (int $amount, int $decimals, string $expected) {
    expect(Money::of($amount, 'EUR')->toDecimal($decimals))->toBe($expected);
})->with([
    [1234, 2, '12.34'],
    [5, 2, '0.05'],
    [-5, 2, '-0.05'],
    [0, 2, '0.00'],
    [1234, 0, '1234'],
    [1234, 3, '1.234'],
]);
