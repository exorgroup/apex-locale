# Changelog

All notable changes to `exorgroup/apex-locale`.

## Unreleased

### Added
- Package skeleton: `LocaleServiceProvider` and `config/locale.php`.
- `Apex\Locale\Models\Model`: base model with the `loc_` table prefix and soft deletes.
- Architecture test: the package never uses `Illuminate\Http`, `Illuminate\Routing` or Inertia,
  and no debugging calls are left behind.
- Test suite (Pest, Testbench, SQLite in memory) and CI for PHP 8.2–8.3 × Laravel 11–12.
- Countries: `loc_countries` (ISO 3166-1, 249 rows; `iso2` plain index; `cash_rounding` override), `Country` model with `options()`, `CountrySeeder` (insert-if-missing, never overwrite, run by the migration), `locale.default_country` sorted first.
- Currencies: `loc_currencies` (ISO 4217, 155 active currencies from the agency's list published 2026-09-17; funds and metals left out; `code` plain index; `decimals`; default `cash_rounding` 1), `Currency::byCode()`, `CurrencySeeder` (insert-if-missing, run by the migration).
- `bin/iso4217.py`: regenerates `database/data/currencies.php` from the agency's list one.
- `Money`: integer minor units + ISO code; plus, minus, times, multiply (ratio), allocate (nothing lost), negate, abs, compare; `splitGross(bp)` (VAT-inclusive, TBX's per-line rule in integers); `roundToStep()`; `toDecimal()`; mixing currencies throws `CurrencyMismatch`; overflow throws. Every rounding is half away from zero.
- `CashRounding::step(currency, country)`: the country's override, else the currency default.
- VAT: `loc_vat_bands` (country, band, label, basis points, effective from / to) with Malta's six bands seeded open-ended; `loc_vat_categories` (tenant-named, pointing at a band); `VatResolver::rate(country, category|band, date)` returning a `VatRate`, throwing `VatRateMissing` (never 0 %) or `VatRateOverlap`.
- `locale.reporting_currency` (default `EUR`) and an empty, dated `loc_exchange_rates` table (`ExchangeRate`: exact decimal rate, two different ISO codes, positive). Nothing converts yet.
- `apex-locale-ai.md`: the agent reference.

### Fixed
- `loc_exchange_rates`' index is named `loc_fx_pair_date_index`: the generated name was 66 characters and MySQL refuses more than 64. A test now checks every `loc_` table and index name.
