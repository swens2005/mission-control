<?php

namespace App\Enums;

/**
 * How a line item is priced: a fixed price per piece, or per hour.
 */
enum LineItemKind: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed price',
            self::Hourly => 'Hourly',
        };
    }

    /**
     * The unit after a quantity: "3 ×" or "1.5 h".
     */
    public function unit(): string
    {
        return match ($this) {
            self::Fixed => '×',
            self::Hourly => 'h',
        };
    }
}
