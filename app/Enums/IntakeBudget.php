<?php

namespace App\Enums;

/**
 * The client's budget bracket on the intake. A choice, never an amount.
 */
enum IntakeBudget: string
{
    case Under2500 = 'under_2500';
    case From2500 = '2500_5000';
    case From5000 = '5000_10000';
    case From10000 = '10000_20000';
    case Over20000 = 'over_20000';
    case Unsure = 'unsure';

    public function label(): string
    {
        return match ($this) {
            self::Under2500 => 'Under €2,500',
            self::From2500 => '€2,500–5,000',
            self::From5000 => '€5,000–10,000',
            self::From10000 => '€10,000–20,000',
            self::Over20000 => 'Over €20,000',
            self::Unsure => 'Not sure yet',
        };
    }
}
