<?php

use Apex\Locale\Exceptions\VatRateMissing;
use Apex\Locale\Exceptions\VatRateOverlap;
use Apex\Locale\Models\VatBand;
use Apex\Locale\Models\VatCategory;
use Apex\Locale\Seeders\VatBandSeeder;
use Apex\Locale\Vat\VatResolver;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->artisan('migrate')->run();
    $this->vat = new VatResolver;
});

// ── the seed ────────────────────────────────────────────────────────────

it('seeds Malta\'s six bands, open-ended', function () {
    expect(VatBand::where('country_code', 'MT')->orderBy('id')->pluck('basis_points', 'band')->all())
        ->toBe(['standard' => 1800, 'reduced' => 1200, 'reduced_2' => 700, 'reduced_3' => 500, 'zero' => 0, 'exempt' => 0])
        ->and(VatBand::whereNotNull('effective_from')->orWhereNotNull('effective_to')->count())->toBe(0)
        ->and(VatBand::where('country_code', '!=', 'MT')->count())->toBe(0);
});

it('never overwrites or re-adds a band on a reseed', function () {
    $standard = VatBand::where('country_code', 'MT')->where('band', 'standard')->first();
    $standard->update(['effective_to' => '2026-12-31']);
    VatBand::where('country_code', 'MT')->where('band', 'zero')->first()->delete();

    (new VatBandSeeder)->run();

    expect(VatBand::withTrashed()->count())->toBe(6)
        ->and($standard->fresh()->effective_to->toDateString())->toBe('2026-12-31')
        ->and(VatBand::where('country_code', 'MT')->where('band', 'zero')->exists())->toBeFalse();
});

// ── the resolver ────────────────────────────────────────────────────────

it('answers a band for a country today', function () {
    $rate = $this->vat->rate('mt', 'standard');

    expect($rate->basisPoints)->toBe(1800)
        ->and($rate->label)->toBe('Standard 18%')
        ->and($rate->country)->toBe('MT')
        ->and($rate->isExempt())->toBeFalse();
});

it('follows a category to its band', function () {
    $hotFood = VatCategory::create(['name' => 'Hot food', 'band' => 'reduced_2']);

    expect($this->vat->rate('MT', $hotFood)->basisPoints)->toBe(700);
});

it('tells exempt apart from zero-rated', function () {
    expect($this->vat->rate('MT', 'exempt')->isExempt())->toBeTrue()
        ->and($this->vat->rate('MT', 'zero')->isExempt())->toBeFalse()
        ->and($this->vat->rate('MT', 'zero')->basisPoints)->toBe(0);
});

it('answers by date across a change in law', function () {
    VatBand::where('country_code', 'MT')->where('band', 'standard')->first()->update(['effective_to' => '2026-12-31']);
    VatBand::create(['country_code' => 'MT', 'band' => 'standard', 'label' => 'Standard 19%', 'basis_points' => 1900, 'effective_from' => '2027-01-01']);

    expect($this->vat->rate('MT', 'standard', CarbonImmutable::parse('2026-12-31'))->basisPoints)->toBe(1800)
        ->and($this->vat->rate('MT', 'standard', CarbonImmutable::parse('2027-01-01'))->basisPoints)->toBe(1900)
        ->and($this->vat->rate('MT', 'standard', CarbonImmutable::parse('2027-01-01'))->effectiveFrom->toDateString())->toBe('2027-01-01');
});

it('refuses a country with no rate, never answering 0 %', function () {
    $this->vat->rate('DE', 'standard');
})->throws(VatRateMissing::class);

it('refuses a band a country does not have', function () {
    $this->vat->rate('MT', 'super_reduced');
})->throws(VatRateMissing::class);

it('refuses a date before a band begins', function () {
    VatBand::create(['country_code' => 'IT', 'band' => 'standard', 'label' => 'Standard 22%', 'basis_points' => 2200, 'effective_from' => '2013-10-01']);

    $this->vat->rate('IT', 'standard', CarbonImmutable::parse('2013-09-30'));
})->throws(VatRateMissing::class);

it('ignores a removed rate', function () {
    VatBand::where('country_code', 'MT')->where('band', 'reduced')->first()->delete();

    $this->vat->rate('MT', 'reduced');
})->throws(VatRateMissing::class);

it('refuses two rates in force on the same day', function () {
    VatBand::create(['country_code' => 'MT', 'band' => 'standard', 'label' => 'Duplicate', 'basis_points' => 1900, 'effective_from' => '2020-01-01']);

    $this->vat->rate('MT', 'standard');
})->throws(VatRateOverlap::class);

// ── guards on the data ──────────────────────────────────────────────────

it('refuses a band row with an unknown band', function () {
    VatBand::create(['country_code' => 'MT', 'band' => 'luxury', 'label' => 'x', 'basis_points' => 2500]);
})->throws(InvalidArgumentException::class);

it('refuses a category with an unknown band', function () {
    VatCategory::create(['name' => 'Yachts', 'band' => 'luxury']);
})->throws(InvalidArgumentException::class);

it('refuses to resolve an unknown band', function () {
    $this->vat->rate('MT', 'luxury');
})->throws(InvalidArgumentException::class);

it('refuses an exempt band with a rate', function () {
    VatBand::create(['country_code' => 'FR', 'band' => 'exempt', 'label' => 'Exempt', 'basis_points' => 100]);
})->throws(InvalidArgumentException::class);

it('lets a rate split a gross line', function () {
    $rate = $this->vat->rate('MT', 'standard');
    $split = Apex\Locale\Money::of(1180, 'EUR')->splitGross($rate->basisPoints);

    expect($split['vat']->amount())->toBe(180);
});
