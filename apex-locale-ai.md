# apex-locale — agent reference

The shared locale reference for Exor products: countries, currencies, `Money`, VAT bands with dated
rates and the rate resolver. Installed by TBX and Apex Till. Read this before writing any code that
handles money, a country, a currency or a VAT rate.

The API here is verified against the source. When a plausible-looking name does **not** exist, it
goes under [Does not exist](#does-not-exist). Do not invent method names.

Decisions live in the host that drives development: apex-tbx `.claude/apextill/phases/LC1/LC1-tasks.md`
(LC1 D1–D11) and `.claude/apextill/decisions.md` (P-D34, P-D37–P-D43, P-D118).

## Setup facts

- Namespace `Apex\Locale\`. **No HTTP, routing or screens anywhere in the package.** The architecture
  test enforces that. A host draws its own pickers.
- Every model extends `Apex\Locale\Models\Model`: tables prefixed `loc_`, every model soft-deletes.
- Config lives under `locale.*`:
  - `default_country` (ISO-2, sorted first; null by default; TBX sets `MT`);
  - `reporting_currency` (default `EUR`).
- **Everything references a country by `iso2` and a currency by its code, never by row id.**
- Seeders live in `src/Seeders` and are **run by their own migrations**. They insert what is missing,
  never overwrite, and treat a soft-deleted row as present. Data files are in `database/data/`.
- Multi-tenancy (later): countries, currencies, VAT bands and FX are landlord data; VAT categories
  are tenant data.

## Money

```php
use Apex\Locale\Money;

$line  = Money::of(1180, 'EUR');               // integer minor units (cents)
$total = $line->times(3)->plus(Money::of(50, 'EUR'));
['net' => $net, 'vat' => $vat] = $line->splitGross(1800);   // 18.00 % in basis points → 1000 + 180
$parts = $total->allocate([1, 1, 1]);          // the parts always sum to the whole
$cash  = $total->roundToStep(CashRounding::step('EUR', 'FI'));   // 5 → nearest 0.05
$total->toDecimal(2);                          // '35.90' — no symbol; presentation is the host's
```

- **No float, ever.** Every rounding is **half away from zero**, PHP `round()`'s rule, so a refund
  mirrors its sale.
- `splitGross(bp)` is TBX's per-line VAT, VAT-inclusive: `vat = round(gross × bp / (10000 + bp))`,
  then `net = gross − vat`. The VAT is rounded first, as TBX's `OrderCalculator` does.
  - An agreement test runs it against TBX's float formula on every amount between ±€1,000.
  - At Malta's rates (18, 12, 7, 5, 0) it agrees on every amount.
  - At 20 %-type rates it differs only at exact half cents, where TBX's float is noise (LC1 D10).
- Mixing currencies throws `Exceptions\CurrencyMismatch`. An integer overflow throws
  `OverflowException`.
- `multiply(num, den)` rounds; `times(int)` is exact.

## Cash rounding

`CashRounding::step(currency, ?countryIso2)` returns, in minor units, the country's
`loc_countries.cash_rounding` if it has one, otherwise the currency's `loc_currencies.cash_rounding`
(default 1). BE, FI, IE and NL are seeded with 5. **Card totals never round.** Ask for a step only when
the payment is cash.

## VAT

```php
use Apex\Locale\Vat\VatResolver;

$rate = app(VatResolver::class)->rate('MT', $item->vatCategory);            // or a band: 'standard'
$rate = app(VatResolver::class)->rate('MT', 'standard', $saleDate);
$rate->basisPoints; $rate->label; $rate->isExempt();
```

- **Two levels (D4):**
  - `loc_vat_bands`: a country's rate for a band over a period. The bands are `VatBand::BANDS`:
    standard, reduced, reduced_2, reduced_3, super_reduced, zero and exempt.
  - `loc_vat_categories`: tenant-named, each pointing at a band.
- **The country is the venue's (P-D38).** No rate in force means `VatRateMissing`, **never 0 %**. Two
  rates in force on the same day mean `VatRateOverlap`.
- Exempt is not zero-rated: `isExempt()` is true, and the invoice says "exempt".
- Malta's six bands are seeded with **no start dates**, meaning "in force before the records" (D11).
  A change in law is a NEW row with `effective_from`; the old row gets `effective_to`. Never edit a
  rate in place, because a past sale must find its rate again.

## Reference data

- `Country::options()` lists the default country first, then the rest by name, as
  `[{value: iso2, label}]`.
- `Currency::byCode('eur')` returns the live currency, or null. `decimals` is the minor unit.
- `database/data/currencies.php` is GENERATED from the ISO 4217 agency's list; regenerate it with
  `bin/iso4217.py`.
- `ExchangeRate` / `loc_exchange_rates` is a shape only: empty, and nothing converts yet.

## Tests

- Pest 3 on Testbench with SQLite in memory. The suites are `Feature` (the database), `Unit` (pure
  `Money`) and `Arch`.
- `SchemaNamesTest` keeps every `loc_` table and index name within MySQL's 64 characters, which
  SQLite does not enforce.

## Traps

1. **A host that already has a table** (TBX had `countries`) must rename it in a migration dated
   BEFORE the package's. Laravel runs every path's migrations in filename order.
2. **MySQL DDL is not transactional.** A failed package migration leaves a half-built table. Fix the
   cause; drop the leftover only once it is shown to be empty and unrecorded.
3. **Long index names.** Name any index over three columns. `SchemaNamesTest` will tell you.
4. **The decimal cast runs on read.** Validate a raw attribute (`getAttributes()`), not the cast
   value: the cast turns `1e3` into `1000` and throws its own error on junk.
5. **Pest datasets of closures** arrive uncalled. Pass names or values, or write separate tests.
6. **Linking the local copy into TBX** (`php tools/packages.php link apex-locale`): stop
   `npm run dev` first, because Vite watches the whole project, `vendor/` included.

## Does not exist

Do not write these. They look reasonable and are not in the source:

| Wrong | Right |
|---|---|
| `Money::format()` | `toDecimal($decimals)`; symbols and grouping are the host's |
| `Money::fromFloat()` / any float input | `Money::of(int $minorUnits, $code)` |
| `$money->convert('GBP')` | nothing: conversion is not built (LC1) |
| `VatResolver::rate()` returning 0 for a missing rate | it throws `VatRateMissing` |
| `$rate->percent` | `$rate->basisPoints` (1800 = 18.00 %) |
| `App\Models\Country` in TBX | `Apex\Locale\Models\Country` (since LC1/007) |
