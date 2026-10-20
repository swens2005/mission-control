<?php

namespace App\Support\Money;

/**
 * Euro amounts as integer cents: parsing what people type, and one display
 * format for every page (ADR 0010). No floats, also not while parsing.
 */
final class Money
{
    /** €10,000,000: far above anything a studio sells, far below overflow. */
    public const int MAX_CENTS = 1_000_000_000;

    /**
     * Parses "1250", "1250.5", "1.250,50", "1,250.50", "€ 99" or "99,95"
     * into cents; null when it isn't a plain, non-negative amount.
     *
     * A separator followed by one or two digits at the end is the decimal
     * one. Anything before it is digits, or digits in groups of three with
     * the other separator ("1.250" is €1,250, as both Dutch and English
     * readers mean it).
     */
    public static function parse(string $input): ?int
    {
        $value = str_replace(['€', ' ', "\u{00A0}"], '', trim($input));

        if (preg_match('/^(?<int>.*?)(?:(?<sep>[.,])(?<frac>\d{1,2}))?$/', $value, $match) !== 1) {
            return null;
        }

        $integer = $match['int'];
        $separator = $match['sep'] ?? '';
        $fraction = $match['frac'] ?? '';

        $plain = preg_match('/^\d+$/', $integer) === 1;
        $grouped = preg_match('/^\d{1,3}(?<g>[.,])\d{3}(?:\k<g>\d{3})*$/', $integer, $groups) === 1;

        if (! $plain && ! $grouped) {
            return null;
        }

        // "1,250,50" or "12.345.6": the same character can't do both jobs.
        if ($grouped && $separator !== '' && $groups['g'] === $separator) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $integer);

        if (strlen($digits) > 10) {
            return null;
        }

        return (int) $digits * 100 + (int) str_pad($fraction, 2, '0');
    }

    /**
     * "€1,234.56"; negative amounts (discounts) as "−€12.50".
     */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '−' : '';
        $cents = abs($cents);

        return $sign.'€'.number_format(intdiv($cents, 100)).'.'.self::pad($cents % 100);
    }

    /**
     * For a form field: "1250.50", or "1250" for whole euros.
     */
    public static function toInput(int $cents): string
    {
        $euros = (string) intdiv($cents, 100);

        return $cents % 100 === 0 ? $euros : $euros.'.'.self::pad($cents % 100);
    }

    private static function pad(int $cents): string
    {
        return str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
    }
}
