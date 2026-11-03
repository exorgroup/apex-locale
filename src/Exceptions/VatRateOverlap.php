<?php

namespace Apex\Locale\Exceptions;

use RuntimeException;

/** Two rates for one country and band are in force on the same date: the data must be fixed. */
class VatRateOverlap extends RuntimeException
{
    public function __construct(public readonly string $country, public readonly string $band, public readonly string $date)
    {
        parent::__construct("More than one {$band} VAT rate is in force for {$country} on {$date}.");
    }
}
