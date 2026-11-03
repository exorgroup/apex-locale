<?php

namespace Apex\Locale;

use Apex\Locale\Models\Country;
use Apex\Locale\Models\Currency;
use InvalidArgumentException;

/**
 * The step a CASH total rounds to, in minor units (LC1 D3, P-D118): the country's override when
 * it has one, otherwise the currency's default. Card totals never round.
 *
 *     $total->roundToStep(CashRounding::step('EUR', 'FI'));   // 5 → to the nearest 0.05
 */
final class CashRounding
{
    public static function step(string $currency, ?string $countryIso2 = null): int
    {
        if ($countryIso2 !== null) {
            $override = Country::where('iso2', strtoupper($countryIso2))->value('cash_rounding');

            if ($override !== null) {
                return (int) $override;
            }
        }

        $currencyStep = Currency::byCode($currency)?->cash_rounding;

        if ($currencyStep === null) {
            throw new InvalidArgumentException("Unknown currency: {$currency}");
        }

        return $currencyStep;
    }
}
