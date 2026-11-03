<?php

namespace Apex\Locale\Vat;

use Apex\Locale\Exceptions\VatRateMissing;
use Apex\Locale\Exceptions\VatRateOverlap;
use Apex\Locale\Models\VatBand;
use Apex\Locale\Models\VatCategory;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Country + category (or band) + date → the rate (LC1/005).
 *
 * The country is the sale's: the venue's country (P-D38). There is NO fallback: a country with no
 * row for the band on that date throws `VatRateMissing` rather than charging 0 % (D5). Two rows in
 * force on the same date throw `VatRateOverlap`, because guessing between them would silently
 * pick a rate.
 *
 *     $rate = app(VatResolver::class)->rate('MT', $item->vatCategory);
 *     $split = $line->splitGross($rate->basisPoints);
 */
final class VatResolver
{
    public function rate(string $country, VatCategory|string $categoryOrBand, ?DateTimeInterface $on = null): VatRate
    {
        $country = strtoupper($country);
        $band = $categoryOrBand instanceof VatCategory ? $categoryOrBand->band : $categoryOrBand;
        $day = ($on ?? now())->format('Y-m-d');

        if (! in_array($band, VatBand::BANDS, true)) {
            throw new InvalidArgumentException("Unknown VAT band: {$band}");
        }

        $rows = VatBand::query()
            ->where('country_code', $country)
            ->where('band', $band)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $day))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $day))
            ->get();

        if ($rows->isEmpty()) {
            throw new VatRateMissing($country, $band, $day);
        }
        if ($rows->count() > 1) {
            throw new VatRateOverlap($country, $band, $day);
        }

        $row = $rows->first();

        return new VatRate($country, $band, $row->label, $row->basis_points, $row->effective_from);
    }
}
