<?php

namespace Apex\Locale\Models;

use InvalidArgumentException;

/**
 * One dated exchange rate (LC1/006, D8): 1 `base_currency` = `rate` `quote_currency` on `effective_on`.
 *
 * Shape only: the table ships empty and nothing converts yet. The rate stays a decimal STRING
 * (`decimal:10`), never a float.
 */
class ExchangeRate extends Model
{
    protected $fillable = ['base_currency', 'quote_currency', 'rate', 'effective_on'];

    protected $casts = [
        'rate' => 'decimal:10',
        'effective_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->base_currency = strtoupper((string) $row->base_currency);
            $row->quote_currency = strtoupper((string) $row->quote_currency);

            foreach ([$row->base_currency, $row->quote_currency] as $code) {
                if (! preg_match('/^[A-Z]{3}$/', $code)) {
                    throw new InvalidArgumentException("Not an ISO 4217 code: {$code}");
                }
            }
            if ($row->base_currency === $row->quote_currency) {
                throw new InvalidArgumentException('An exchange rate needs two different currencies.');
            }
            // A plain positive decimal, checked as the RAW text given: reading `$row->rate` would run
            // the decimal cast first, turning '1e3' into 1000 and throwing its own error on junk.
            $rate = (string) ($row->getAttributes()['rate'] ?? '');
            if (! preg_match('/^\d+(\.\d+)?$/', $rate) || ! preg_match('/[1-9]/', $rate)) {
                throw new InvalidArgumentException('An exchange rate must be positive.');
            }
        });
    }
}
