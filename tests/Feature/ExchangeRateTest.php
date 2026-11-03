<?php

use Apex\Locale\Models\ExchangeRate;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('reports in EUR by default', function () {
    expect(config('locale.reporting_currency'))->toBe('EUR');
});

it('ships the exchange-rate table empty', function () {
    expect(ExchangeRate::withTrashed()->count())->toBe(0);
});

it('keeps a rate exactly, as a decimal string', function () {
    ExchangeRate::create(['base_currency' => 'eur', 'quote_currency' => 'gbp', 'rate' => '0.8612345678', 'effective_on' => '2026-11-04']);

    $row = ExchangeRate::first();

    expect($row->rate)->toBe('0.8612345678')
        ->and($row->base_currency)->toBe('EUR')
        ->and($row->quote_currency)->toBe('GBP')
        ->and($row->effective_on->toDateString())->toBe('2026-11-04');
});

it('refuses a rate that is not a positive decimal', function (string $rate) {
    ExchangeRate::create(['base_currency' => 'EUR', 'quote_currency' => 'GBP', 'rate' => $rate, 'effective_on' => '2026-11-04']);
})->with(['0', '0.0000', '-1', 'abc', '1e3', ''])->throws(InvalidArgumentException::class);

it('refuses the same currency on both sides', function () {
    ExchangeRate::create(['base_currency' => 'EUR', 'quote_currency' => 'eur', 'rate' => '1', 'effective_on' => '2026-11-04']);
})->throws(InvalidArgumentException::class);

it('refuses a code that is not three letters', function () {
    ExchangeRate::create(['base_currency' => 'EURO', 'quote_currency' => 'GBP', 'rate' => '1', 'effective_on' => '2026-11-04']);
})->throws(InvalidArgumentException::class);
