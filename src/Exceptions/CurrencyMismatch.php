<?php

namespace Apex\Locale\Exceptions;

use DomainException;

/** Two amounts in different currencies met in one operation (P-D41: mixing currencies throws). */
class CurrencyMismatch extends DomainException
{
    public function __construct(public readonly string $left, public readonly string $right)
    {
        parent::__construct("Cannot combine {$left} with {$right}.");
    }
}
