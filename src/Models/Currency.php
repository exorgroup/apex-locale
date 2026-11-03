<?php

namespace Apex\Locale\Models;

/**
 * An ISO 4217 currency (LC1/003).
 *
 * Referenced by its alphabetic `code`, never by `id`. `decimals` is the minor unit; `cash_rounding`
 * is the default cash step in minor units (a country may override it).
 */
class Currency extends Model
{
    protected $fillable = ['code', 'numeric_code', 'name', 'decimals', 'cash_rounding'];

    protected $casts = [
        'decimals' => 'integer',
        'cash_rounding' => 'integer',
    ];

    /** The live currency for a code, or null. */
    public static function byCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))->first();
    }
}
