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

    public static function fixed(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('A discount cannot be negative.');
        }

        return new self(null, $cents);
    }

    /**
     * The discount on this subtotal, in cents. A fixed discount larger than
     * the subtotal is refused: a proposal never goes below zero.
     */
    public function amountOf(int $subtotal): int
    {
        if ($this->basisPoints !== null) {
            return ProposalTotals::roundedShare($subtotal, $this->basisPoints);
        }

        if ($this->cents > $subtotal) {
            throw new InvalidArgumentException('The discount is larger than the subtotal.');
        }

        return (int) $this->cents;
    }
}
