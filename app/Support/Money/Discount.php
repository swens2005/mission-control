<?php

namespace App\Support\Money;

use InvalidArgumentException;

/**
 * A proposal-level discount: a percentage (in basis points) or a fixed
 * amount in cents, taken off before VAT (ADR 0010).
 */
final readonly class Discount
{
    private function __construct(
        public ?int $basisPoints,
        public ?int $cents,
    ) {}

    public static function percentage(int $basisPoints): self
    {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new InvalidArgumentException('A percentage discount is between 0% and 100%.');
        }

        return new self($basisPoints, null);
    }

    /**
     * "10", "12.5" or "12,5" (percent) as basis points; null otherwise.
     */
    public static function parsePercentage(string $input): ?int
    {
        if (preg_match('/^(\d{1,3})(?:[.,](\d{1,2}))?\s*%?$/', trim($input), $match) !== 1) {
            return null;
        }

        return (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
    }

    /**
     * Basis points as "12.5%".
     */
    public static function formatPercentage(int $basisPoints): string
    {
        return Quantity::format($basisPoints).'%';
    }

    public static function fixed(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('A discount cannot be negative.');
        }

        return new self(null, $cents);
    }

    /**
     * The discount on this subtotal, in cents. A fixed discount never takes
     * more than the subtotal, so a proposal never goes below zero (the form
     * refuses a larger one; removing lines later can still get here).
     */
    public function amountOf(int $subtotal): int
    {
        if ($this->basisPoints !== null) {
            return ProposalTotals::roundedShare($subtotal, $this->basisPoints);
        }

        return min((int) $this->cents, $subtotal);
    }

    public function exceeds(int $subtotal): bool
    {
        return $this->cents !== null && $this->cents > $subtotal;
    }
}
