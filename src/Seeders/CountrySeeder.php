<?php

namespace Apex\Locale\Seeders;

use Illuminate\Support\Facades\DB;

/**
 * ISO 3166-1 into `loc_countries` (moved from TBX's AT/002, LC1/002).
 *
 * INSERT-IF-MISSING, never overwrite. `loc_countries` is a support table that grows
 * operator-owned switches, and a reseed must not undo one. A soft-deleted row counts as present:
 * a country somebody removed stays removed.
 *
 * Run by the table's own migration, so a deploy that runs `migrate` and never `db:seed` still has
 * the list a `country_code` rule checks against. An empty table would refuse every address.
 *
 * `config('locale.default_country')` is sorted first; every other country is alphabetical.
 */
class CountrySeeder
{
    public function run(): void
    {
        $present = DB::table('loc_countries')->pluck('iso2')->all();
        $cashRounding = require __DIR__.'/../../database/data/cash_rounding.php';
        $default = config('locale.default_country');
        $now = now();

        $rows = [];
        foreach (require __DIR__.'/../../database/data/countries.php' as [$iso2, $iso3, $name]) {
            if (in_array($iso2, $present, true)) {
                continue;
            }

            $rows[] = [
                'iso2' => $iso2,
                'iso3' => $iso3,
                'name' => $name,
                'sort_order' => $iso2 === $default ? 0 : 100,
                'cash_rounding' => $cashRounding[$iso2] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('loc_countries')->insert($chunk);
        }
    }
}
