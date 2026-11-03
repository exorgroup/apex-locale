<?php

namespace Apex\Locale\Models;

/**
 * An ISO 3166-1 country (LC1/002), moved from TBX's AT/002.
 *
 * Everything that references a country stores `iso2`, never `id`.
 * `cash_rounding` overrides the currency's cash step for cash taken here (LC1 D3).
 */
class Country extends Model
{
    protected $fillable = ['iso2', 'iso3', 'name', 'sort_order', 'cash_rounding'];

    protected $casts = [
        'sort_order' => 'integer',
        'cash_rounding' => 'integer',
    ];

    /**
     * Chooser options, in the chooser's order: the default country first (`sort_order` 0),
     * then by name.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['iso2', 'name'])
            ->map(fn (self $c) => ['value' => $c->iso2, 'label' => $c->name])
            ->all();
    }
}
