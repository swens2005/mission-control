<?php

namespace App\Enums;

/**
 * A proposal's discount: none, a percentage, or a fixed amount.
 */
enum DiscountType: string
{
    case None = 'none';
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No discount',
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }
}
