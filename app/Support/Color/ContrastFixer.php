<?php

namespace App\Support\Color;

/**
 * "Fix it" (story 21): the nearest lightness at which a color reaches a
 * contrast target on a background, keeping hue (and chroma, where the
 * screen can show it). Moving only lightness keeps the color's character,
 * which is why Palette Lab works in OKLCH.
 */
final class ContrastFixer
{
    /** Coarse search step, then refined one stored step at a time. */
    private const STEP = 25;

    /**
     * The closest passing color, or null if no lightness passes (for
     * example a 7:1 target on a mid-grey background).
     */
    public static function fix(OklchColor $color, string $background, float $target): ?OklchColor
    {
        if (self::passes($color, $background, $target)) {
            return Oklch::inGamut($color);
        }

        $darker = self::search($color, $background, $target, -1);
        $lighter = self::search($color, $background, $target, 1);

        if ($darker === null || $lighter === null) {
            return $darker ?? $lighter;
        }

        $toDarker = $color->lightness - $darker->lightness;
        $toLighter = $lighter->lightness - $color->lightness;

        // Equally close: prefer darker, the usual fix for text on light.
        return $toLighter < $toDarker ? $lighter : $darker;
    }

    public static function ratio(OklchColor $color, string $background): float
    {
        return Contrast::ratio(Oklch::toHex($color), $background);
    }

    private static function passes(OklchColor $color, string $background, float $target): bool
    {
        return self::ratio($color, $background) >= $target;
    }

    /**
     * Walks lightness in one direction: coarse steps until it passes, then
     * back one stored step at a time to the first passing value.
     */
    private static function search(OklchColor $color, string $background, float $target, int $direction): ?OklchColor
    {
        $lightness = $color->lightness;

        while (true) {
            $next = $lightness + $direction * self::STEP;
            $next = max(0, min(OklchColor::MAX_LIGHTNESS, $next));

            if (self::passes($color->withLightness($next), $background, $target)) {
                // Refine: the closest passing value between the two.
                for ($l = $lightness + $direction; $l !== $next; $l += $direction) {
                    if (self::passes($color->withLightness($l), $background, $target)) {
                        return Oklch::inGamut($color->withLightness($l));
                    }
                }

                return Oklch::inGamut($color->withLightness($next));
            }

            if ($next === 0 || $next === OklchColor::MAX_LIGHTNESS) {
                return null;
            }

            $lightness = $next;
        }
    }
}
