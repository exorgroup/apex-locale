<?php

namespace Apex\Locale\Models;

use InvalidArgumentException;

/**
 * A tenant-named VAT category ("Hot food", "Event tickets") pointing at a band (LC1/005, D4).
 *
 * Items point at a category; the category points at a band; the band's rate comes from the
 * country of the sale and the date (`Apex\Locale\Vat\VatResolver`). When a country changes a
 * rate, every category in that band follows with no edit here.
 * Multi-tenancy: tenant data (D9).
 */
class VatCategory extends Model
{
    protected $fillable = ['name', 'band'];

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (! in_array($category->band, VatBand::BANDS, true)) {
                throw new InvalidArgumentException("Unknown VAT band: {$category->band}");
            }
        });
    }
}
