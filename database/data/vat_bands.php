<?php

/**
 * VAT rate bands per country (LC1/005, D4–D6). Malta only (D5); the shape takes more countries.
 *
 * The rates are TBX's own (`tax_rates`, 2026-11-04). `effective_from` is NULL: in force before this
 * table's records begin. Their legal start dates are not recorded here rather than guessed. A
 * change in law is a NEW row with its date, and the old row gets an `effective_to`.
 *
 * Read by `Apex\Locale\Seeders\VatBandSeeder`: insert-if-missing per country and band, never
 * overwrite.
 *
 * @return list<array{0: string, 1: string, 2: string, 3: int}> [country iso2, band, label, basis points]
 */
return [
    ['MT', 'standard', 'Standard 18%', 1800],
    ['MT', 'reduced', 'Reduced 12%', 1200],
    ['MT', 'reduced_2', 'Reduced 7%', 700],
    ['MT', 'reduced_3', 'Reduced 5%', 500],
    ['MT', 'zero', 'Zero-rated', 0],
    ['MT', 'exempt', 'Exempt', 0],
];
