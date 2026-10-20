<?php

namespace App\Support\Money;

/**
 * Quantities as integer hundredths (1.25 hours = 125), in quarter steps
 * (ADR 0010). Parsed from "1.5" or "1,5"; never through a float.
 */
final class Quantity
{
    public const int STEP = 25;

    /** 10,000 hours or pieces. */
    public const int MAX = 1_000_000;

    /**
     * Hundredths, or null when it isn't a plain positive number with at
     * most two decimals. Quarter steps are checked by the caller's rules.
     */
    public static function parse(string $input): ?int
    {
        if (preg_match('/^(\d{1,6})(?:[.,](\d{1,2}))?$/', trim($input), $match) !== 1) {
            return null;
        }

        return (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
    }

    /**
     * "1", "1.5", "0.25".
     */
    public static function format(int $hundredths): string
    {
        $whole = (string) intdiv($hundredths, 100);
        $fraction = $hundredths % 100;

        if ($fraction === 0) {
            return $whole;
        }

        return $whole.'.'.rtrim(str_pad((string) $fraction, 2, '0', STR_PAD_LEFT), '0');
    }
}
