<?php

use Apex\Locale\CashRounding;
use Apex\Locale\Models\Currency;
use Apex\Locale\Money;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('uses the country override where there is one', function () {
    expect(CashRounding::step('EUR', 'FI'))->toBe(5)
        ->and(CashRounding::step('EUR', 'nl'))->toBe(5);
});

it('falls back to the currency default', function () {
    expect(CashRounding::step('EUR', 'MT'))->toBe(1)
        ->and(CashRounding::step('EUR'))->toBe(1);
});

it('follows an edited currency default', function () {
    Currency::byCode('CHF')->update(['cash_rounding' => 5]);

    expect(CashRounding::step('CHF', 'CH'))->toBe(5);
});

it('refuses an unknown currency', function () {
    CashRounding::step('XYZ', 'MT');
})->throws(InvalidArgumentException::class);

it('rounds a Finnish cash total to five cents and leaves a Maltese one alone', function () {
    $total = Money::of(1233, 'EUR');

    expect($total->roundToStep(CashRounding::step('EUR', 'FI'))->amount())->toBe(1235)
        ->and($total->roundToStep(CashRounding::step('EUR', 'MT'))->amount())->toBe(1233);
});
