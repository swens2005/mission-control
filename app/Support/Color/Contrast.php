<?php

namespace App\Support\Color;

use InvalidArgumentException;

/**
 * WCAG 2.x contrast ratios between two sRGB colors.
 *
 * @see https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
 */
final class Contrast
{
    /** Minimum ratio for normal text (AA). */
    public const AA_TEXT = 4.5;

    /** Minimum ratio for large text and UI components (AA). */
    public const AA_LARGE = 3.0;

    /** Minimum ratio for normal text (AAA). */
    public const AAA_TEXT = 7.0;

    /**
     * The contrast ratio between two colors, from 1 (none) to 21.
     */
    public static function ratio(string $foreground, string $background): float
    {
        $a = self::relativeLuminance($foreground);
        $b = self::relativeLuminance($background);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /**
     * Relative luminance of a #rrggbb color, from 0 (black) to 1 (white).
     */
    public static function relativeLuminance(string $hex): float
    {
        if (preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $m) !== 1) {
            throw new InvalidArgumentException("Not a #rrggbb color: {$hex}");
        }

        [$r, $g, $b] = array_map(function (string $channel): float {
            $c = hexdec($channel) / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, [$m[1], $m[2], $m[3]]);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
