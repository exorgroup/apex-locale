<?php

namespace Apex\Locale\Models;

use InvalidArgumentException;

/**
 * One country's rate for one band, over a period (LC1/005, D4).
 *
 * `basis_points`: 1800 = 18.00 % (D6). `effective_from` NULL means "since before the records";
 * `effective_to` NULL means "until further notice". A change in law is a new row; the old row is
 * closed with an `effective_to`, never edited, so a past sale's rate can always be found again.
 * Multi-tenancy: landlord data (D9).
 */
class VatBand extends Model
{
    /** The bands a country can have. A country uses the ones its law has. */
    public const BANDS = ['standard', 'reduced', 'reduced_2', 'reduced_3', 'super_reduced', 'zero', 'exempt'];

    protected $fillable = ['country_code', 'band', 'label', 'basis_points', 'effective_from', 'effective_to'];

    protected $casts = [
        'basis_points' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            if (! in_array($row->band, self::BANDS, true)) {
                throw new InvalidArgumentException("Unknown VAT band: {$row->band}");
            }
            if ($row->band === 'exempt' && $row->basis_points !== 0) {
                throw new InvalidArgumentException('An exempt band has no rate.');
            }
        });
    }

    public function isExempt(): bool
    {
        return $this->band === 'exempt';
    }
}
