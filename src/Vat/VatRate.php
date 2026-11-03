<?php

namespace Apex\Locale\Vat;

use DateTimeInterface;

/** The rate that applies to one sale: what `VatResolver` answers (LC1/005). */
final class VatRate
{
    public function __construct(
        public readonly string $country,
        public readonly string $band,
        public readonly string $label,
        public readonly int $basisPoints,
        public readonly ?DateTimeInterface $effectiveFrom,
    ) {}

    /** Exempt is not "0 %": an invoice says "exempt", and it reports apart from zero-rated. */
    public function isExempt(): bool
    {
        return $this->band === 'exempt';
    }
}
