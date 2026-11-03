<?php

use Apex\Locale\Models\Country;
use Apex\Locale\Seeders\CountrySeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The migration runs the seeder; every test starts from that state.

beforeEach(function () {
    config()->set('locale.default_country', 'MT');
    $this->artisan('migrate')->run();
});

it('seeds all 249 ISO 3166-1 countries', function () {
    expect(Country::count())->toBe(249)
        ->and(Country::where('iso2', 'MT')->value('iso3'))->toBe('MLT');
});

it('ships a data file with unique, well-formed codes', function () {
    $rows = require __DIR__.'/../../database/data/countries.php';

    expect($rows)->toHaveCount(249)
        ->and(array_unique(array_column($rows, 0)))->toHaveCount(249)
        ->and(array_unique(array_column($rows, 1)))->toHaveCount(249);

    foreach ($rows as [$iso2, $iso3, $name]) {
        expect($iso2)->toMatch('/^[A-Z]{2}$/')
            ->and($iso3)->toMatch('/^[A-Z]{3}$/')
            ->and($name)->not->toBeEmpty();
    }
});

it('puts the default country first, then sorts by name', function () {
    $options = Country::options();

    expect($options[0])->toBe(['value' => 'MT', 'label' => 'Malta'])
        ->and($options[1]['label'])->toBe('Afghanistan');
});

it('sorts plainly when no default country is set', function () {
    config()->set('locale.default_country', null);
    DB::table('loc_countries')->delete();
    (new CountrySeeder)->run();

    expect(Country::options()[0]['label'])->toBe('Afghanistan');
});

it('seeds the cash-rounding override for FI, NL, IE and BE only', function () {
    expect(Country::whereNotNull('cash_rounding')->orderBy('iso2')->pluck('cash_rounding', 'iso2')->all())
        ->toBe(['BE' => 5, 'FI' => 5, 'IE' => 5, 'NL' => 5])
        ->and(Country::where('iso2', 'MT')->value('cash_rounding'))->toBeNull();
});

it('never overwrites a row on a reseed', function () {
    Country::where('iso2', 'MT')->update(['name' => 'Malta (edited)', 'cash_rounding' => 10]);

    (new CountrySeeder)->run();

    $malta = Country::where('iso2', 'MT')->first();
    expect($malta->name)->toBe('Malta (edited)')
        ->and($malta->cash_rounding)->toBe(10)
        ->and(Country::count())->toBe(249);
});

it('keeps a removed country removed on a reseed', function () {
    Country::where('iso2', 'AQ')->first()->delete();

    (new CountrySeeder)->run();

    expect(Country::where('iso2', 'AQ')->exists())->toBeFalse()
        ->and(Country::withTrashed()->where('iso2', 'AQ')->count())->toBe(1);
});

it('indexes iso2 plainly, never uniquely', function () {
    $iso2 = collect(Schema::getIndexes('loc_countries'))
        ->filter(fn ($index) => $index['columns'] === ['iso2']);

    expect($iso2)->toHaveCount(1)
        ->and($iso2->first()['unique'])->toBeFalse();
});
