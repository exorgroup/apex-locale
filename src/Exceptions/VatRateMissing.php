<?php

namespace Apex\Locale\Exceptions;

use RuntimeException;

/** No rate is recorded for this country, band and date. Never treated as 0 % (LC1 D5). */
class VatRateMissing extends RuntimeException
{
    public function __construct(public readonly string $country, public readonly string $band, public readonly string $date)
    {
        parent::__construct("No {$band} VAT rate recorded for {$country} on {$date}.");
    }
}
