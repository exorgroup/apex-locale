<?php

namespace Apex\Locale;

use Apex\Locale\Exceptions\CurrencyMismatch;
use InvalidArgumentException;
use OverflowException;

/**
 * An amount of money: an integer in the currency's minor unit, and an ISO 4217 code (P-D41, LC1/004).
 *
 * No float ever enters the arithmetic. Every rounding is HALF AWAY FROM ZERO, PHP `round()`'s
 * rule, which TBX's calculators use, so a refund (negative) rounds exactly as the sale did.
 * Mixing currencies throws; there is no conversion here (LC1 roadmap: not in it).
 *
 * Immutable: every operation returns a new instance.
 */
final class Money
{
    private function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {}

    public static function of(int $amount, string $currency): self
    {
        $code = strtoupper($currency);

        if (! preg_match('/^[A-Z]{3}$/', $code)) {
            throw new InvalidArgumentException("Not an ISO 4217 code: {$currency}");
        }

        return new self($amount, $code);
    }

    public static function zero(string $currency): self
    {
        return self::of(0, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    // ── arithmetic ──────────────────────────────────────────────────────

    public function plus(self $other): self
    {
        $this->sameCurrency($other);

        return new self(self::checked($this->amount + $other->amount), $this->currency);
    }

    public function minus(self $other): self
    {
        $this->sameCurrency($other);

        return new self(self::checked($this->amount - $other->amount), $this->currency);
    }

    /** A whole quantity: exact, no rounding. */
    public function times(int $quantity): self
    {
        return new self(self::checked($this->amount * $quantity), $this->currency);
    }

    /** By a ratio `numerator / denominator`, rounded half away from zero. */
    public function multiply(int $numerator, int $denominator): self
    {
        return new self(self::roundDiv(self::checked($this->amount * $numerator), $denominator), $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }

    public function abs(): self
    {
        return new self(abs($this->amount), $this->currency);
    }

    /**
     * Split into parts by whole-number ratios, losing nothing: the parts always sum to this amount.
     * The units left over after the proportional split go one each to the first parts, so the
     * result is deterministic. A negative amount allocates its negative units the same way.
     *
     * @param  list<int>  $ratios  non-negative, not all zero
     * @return list<self>
     */
    public function allocate(array $ratios): array
    {
        $total = array_sum($ratios);

        if ($ratios === [] || $total <= 0 || min($ratios) < 0) {
            throw new InvalidArgumentException('Ratios must be non-negative and not all zero.');
        }

        $sign = $this->amount < 0 ? -1 : 1;
        $remaining = abs($this->amount);
        $parts = [];

        foreach ($ratios as $ratio) {
            $share = intdiv(self::checked(abs($this->amount) * $ratio), $total);
            $parts[] = $share;
            $remaining -= $share;
        }

        for ($i = 0; $remaining > 0; $i = ($i + 1) % count($parts)) {
            if ($ratios[$i] === 0) {
                continue;
            }
            $parts[$i]++;
            $remaining--;
        }

        return array_map(fn (int $part) => new self($sign * $part, $this->currency), $parts);
    }

    // ── VAT ─────────────────────────────────────────────────────────────

    /**
     * Split a VAT-INCLUSIVE gross amount into net and VAT, for one line (LC1 D7, D55).
     *
     * Mirrors TBX's `OrderCalculator` exactly: the VAT is computed and rounded first, then the
     * net is what remains. `vat = round(gross × bp / (10000 + bp))`, half away from zero; that is
     * TBX's `round($gross - $gross / (1 + $rate / 100), 2)` done in integers.
     *
     * @return array{net: self, vat: self}
     */
    public function splitGross(int $basisPoints): array
    {
        if ($basisPoints < 0) {
            throw new InvalidArgumentException('A VAT rate cannot be negative.');
        }

        $vat = self::roundDiv(self::checked($this->amount * $basisPoints), 10000 + $basisPoints);

        return [
            'net' => new self($this->amount - $vat, $this->currency),
            'vat' => new self($vat, $this->currency),
        ];
    }

    // ── cash ────────────────────────────────────────────────────────────

    /**
     * Round to the nearest multiple of `step` minor units, half away from zero: cash totals in a
     * country that rounds (LC1 D3). Use `CashRounding::step()` for the step; card totals never round.
     */
    public function roundToStep(int $step): self
    {
        if ($step < 1) {
            throw new InvalidArgumentException('A rounding step is at least one minor unit.');
        }

        return new self(self::roundDiv($this->amount, $step) * $step, $this->currency);
    }

    // ── comparison ──────────────────────────────────────────────────────

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->amount === $other->amount;
    }

    public function compare(self $other): int
    {
        $this->sameCurrency($other);

        return $this->amount <=> $other->amount;
    }

    public function greaterThan(self $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function lessThan(self $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    // ── display ─────────────────────────────────────────────────────────

    /**
     * The amount as a plain decimal string in the currency's minor unit: `1234` with 2 decimals →
     * `12.34`, `-5` → `-0.05`. No symbol and no grouping: presentation is the host's.
     */
    public function toDecimal(int $decimals): string
    {
        if ($decimals === 0) {
            return (string) $this->amount;
        }

        $digits = str_pad((string) abs($this->amount), $decimals + 1, '0', STR_PAD_LEFT);

        return ($this->amount < 0 ? '-' : '').substr($digits, 0, -$decimals).'.'.substr($digits, -$decimals);
    }

    // ── internals ───────────────────────────────────────────────────────

    private function sameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatch($this->currency, $other->currency);
        }
    }

    /** Integer division rounded half away from zero; the divisor must be positive. */
    private static function roundDiv(int $dividend, int $divisor): int
    {
        if ($divisor < 1) {
            throw new InvalidArgumentException('The divisor must be positive.');
        }

        $quotient = intdiv($dividend, $divisor);
        $remainder = $dividend % $divisor;

        if (2 * abs($remainder) >= $divisor) {
            $quotient += $dividend < 0 ? -1 : 1;
        }

        return $quotient;
    }

    /** An integer result that overflowed became a float; refuse it rather than lose precision. */
    private static function checked(int|float $value): int
    {
        if (! is_int($value)) {
            throw new OverflowException('Money arithmetic overflowed the integer range.');
        }

        return $value;
    }
}
