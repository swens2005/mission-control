<?php

namespace App\Support\Money;

use InvalidArgumentException;

/**
 * The sums of a proposal, in integer cents (ADR 0010):
 *
 *     line total = unit price × quantity, rounded half up to the cent
 *     subtotal   = sum of line totals
 *     discount   = a percentage (rounded once) or a fixed amount
 *     VAT        = 21% of (subtotal − discount), rounded once, on the total
 *     total      = subtotal − discount + VAT
 *
 * Rounding is integer arithmetic only: intdiv(a × b + divisor / 2, divisor).
 */
final readonly class ProposalTotals
{
    /** 21%, in basis points. */
    public const int VAT_RATE = 2100;

    /**
     * @param  list<int>  $lineTotals
     */
    private function __construct(
        public array $lineTotals,
        public int $subtotal,
        public int $discount,
        public int $vat,
        public int $total,
    ) {}

    /**
     * @param  list<array{unitCents: int, quantity: int}>  $lines  quantity in hundredths
     */
    public static function calculate(array $lines, ?Discount $discount = null): self
    {
        $lineTotals = array_map(function (array $line): int {
            if ($line['unitCents'] < 0 || $line['quantity'] < 0) {
                throw new InvalidArgumentException('Prices and quantities cannot be negative.');
            }

            return self::lineTotal($line['unitCents'], $line['quantity']);
        }, $lines);

        $subtotal = array_sum($lineTotals);
        $discountCents = $discount?->amountOf($subtotal) ?? 0;
        $net = $subtotal - $discountCents;
        $vat = self::roundedShare($net, self::VAT_RATE);

        return new self($lineTotals, $subtotal, $discountCents, $vat, $net + $vat);
    }

    public static function lineTotal(int $unitCents, int $quantity): int
    {
        return intdiv($unitCents * $quantity + 50, 100);
    }

    /**
     * $basisPoints of $cents, rounded half up (10% = 1000 basis points).
     */
    public static function roundedShare(int $cents, int $basisPoints): int
    {
        return intdiv($cents * $basisPoints + 5000, 10000);
    }

    /**
     * Subtotal after the discount, before VAT.
     */
    public function net(): int
    {
        return $this->subtotal - $this->discount;
    }

    /**
     * @return array{lineTotals: list<int>, subtotal: int, discount: int, net: int, vat: int, total: int}
     */
    public function toArray(): array
    {
        return [
            'lineTotals' => $this->lineTotals,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'net' => $this->net(),
            'vat' => $this->vat,
            'total' => $this->total,
        ];
    }
}
