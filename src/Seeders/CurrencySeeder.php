<?php

namespace Apex\Locale\Seeders;

use Illuminate\Support\Facades\DB;

/**
 * ISO 4217 into `loc_currencies` (LC1/003).
 *
 * INSERT-IF-MISSING, never overwrite, a soft-deleted row counts as present. These are the same
 * rules as `CountrySeeder`, for the same reason: a reseed must never undo an operator's change.
 * Run by the table's own migration.
 *
 * Every currency's default cash step is its smallest unit (1). A country that rounds cash more
 * coarsely overrides it on `loc_countries.cash_rounding` (LC1 D3). Per-currency steps for anything
 * but EUR are set when that currency first goes live (P-D37: EUR only at go-live).
 */
class CurrencySeeder
{
    public function run(): void
    {
        $present = DB::table('loc_currencies')->pluck('code')->all();
        $now = now();

        $rows = [];
        foreach (require __DIR__.'/../../database/data/currencies.php' as [$code, $numeric, $name, $decimals]) {
            if (in_array($code, $present, true)) {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'numeric_code' => $numeric,
                'name' => $name,
                'decimals' => $decimals,
                'cash_rounding' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('loc_currencies')->insert($chunk);
        }
    }
}
