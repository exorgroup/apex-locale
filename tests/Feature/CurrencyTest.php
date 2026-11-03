<?php

use Apex\Locale\Models\Currency;
use Apex\Locale\Seeders\CurrencySeeder;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('seeds the 155 ISO 4217 currencies of the data file', function () {
    expect(Currency::count())->toBe(155);
});

it('ships a data file with unique, well-formed codes', function () {
    $rows = require __DIR__.'/../../database/data/currencies.php';

    expect(array_unique(array_column($rows, 0)))->toHaveCount(count($rows))
        ->and(array_unique(array_column($rows, 1)))->toHaveCount(count($rows));

    foreach ($rows as [$code, $numeric, $name, $decimals]) {
        expect($code)->toMatch('/^[A-Z]{3}$/')
            ->and($numeric)->toMatch('/^\d{3}$/')
            ->and($name)->not->toBeEmpty()
            ->and($decimals)->toBeIn([0, 2, 3]);
    }
});

it('knows the minor units that matter', function (string $code, string $numeric, int $decimals) {
    $currency = Currency::byCode($code);

    expect($currency->numeric_code)->toBe($numeric)
        ->and($currency->decimals)->toBe($decimals);
})->with([
    ['EUR', '978', 2],
    ['GBP', '826', 2],
    ['USD', '840', 2],
    ['JPY', '392', 0],
    ['KWD', '414', 3],
]);

it('finds a currency by a lower-case code too', function () {
    expect(Currency::byCode('eur')?->name)->toBe('Euro')
        ->and(Currency::byCode('XYZ'))->toBeNull();
});

it('leaves out funds and metals', function () {
    expect(Currency::whereIn('code', ['XAU', 'XAG', 'XDR', 'XXX', 'XTS', 'CLF', 'BOV', 'CHE'])->count())->toBe(0);
});

it('defaults every cash step to the smallest unit', function () {
    expect(Currency::where('cash_rounding', '!=', 1)->count())->toBe(0);
});

it('never overwrites a row on a reseed', function () {
    Currency::byCode('EUR')->update(['name' => 'Euro (edited)', 'cash_rounding' => 5]);

    (new CurrencySeeder)->run();

    $euro = Currency::byCode('EUR');
    expect($euro->name)->toBe('Euro (edited)')
        ->and($euro->cash_rounding)->toBe(5)
        ->and(Currency::count())->toBe(155);
});

it('keeps a removed currency removed on a reseed', function () {
    Currency::byCode('ZWG')->delete();

    (new CurrencySeeder)->run();

    expect(Currency::byCode('ZWG'))->toBeNull()
        ->and(Currency::withTrashed()->where('code', 'ZWG')->count())->toBe(1);
});

it('indexes code plainly, never uniquely', function () {
    $code = collect(Schema::getIndexes('loc_currencies'))
        ->filter(fn ($index) => $index['columns'] === ['code']);

    expect($code)->toHaveCount(1)
        ->and($code->first()['unique'])->toBeFalse();
});
