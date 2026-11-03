<?php

namespace Apex\Locale\Seeders;

use Illuminate\Support\Facades\DB;

/**
 * VAT bands into `loc_vat_bands` (LC1/005).
 *
 * INSERT-IF-MISSING per country AND band, never overwrite: a country that already has ANY row for a
 * band, at any date, removed or not, is left alone. A reseed must never re-open a closed rate or
 * undo a dated change.
 */
class VatBandSeeder
{
    public function run(): void
    {
        $present = DB::table('loc_vat_bands')->get(['country_code', 'band'])
            ->map(fn ($row) => $row->country_code.'|'.$row->band)
            ->all();
        $now = now();

        $rows = [];
        foreach (require __DIR__.'/../../database/data/vat_bands.php' as [$country, $band, $label, $basisPoints]) {
            if (in_array($country.'|'.$band, $present, true)) {
                continue;
            }

            $rows[] = [
                'country_code' => $country,
                'band' => $band,
                'label' => $label,
                'basis_points' => $basisPoints,
                'effective_from' => null,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('loc_vat_bands')->insert($rows);
        }
    }
}
