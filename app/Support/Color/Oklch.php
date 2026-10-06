<?php

namespace App\Support\Color;

use InvalidArgumentException;

/**
 * Conversions between sRGB hex and OKLCH (story 20), after Björn Ottosson's
 * OKLab (https://bottosson.github.io/posts/oklab/).
 *
 * OKLCH is "perceptual": equal steps in lightness look equal, and changing
 * lightness keeps the hue. That's why Palette Lab's "Fix it" (story 21)
 * only moves lightness.
 */
final class Oklch
{
    /** Channels this far outside 0-1 still count as in gamut (rounding). */
    private const GAMUT_EPSILON = 0.0001;

    public static function fromHex(string $hex): OklchColor
    {
        [$r, $g, $b] = array_map(self::toLinear(...), self::hexToChannels($hex));

        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        [$l, $m, $s] = [self::cbrt($l), self::cbrt($m), self::cbrt($s)];

        $okL = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $okA = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $okB = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        $chroma = sqrt($okA ** 2 + $okB ** 2);
        // Greys have no meaningful hue; keep it at 0 so it's stable.
        $hue = $chroma < 0.0002 ? 0.0 : rad2deg(atan2($okB, $okA));

        return OklchColor::fromFloats($okL, $chroma < 0.0002 ? 0.0 : $chroma, $hue < 0 ? $hue + 360 : $hue);
    }

    /**
     * The hex for an OKLCH color. Colors the screen can't show are brought
     * into sRGB by lowering chroma only, so lightness and hue stay put.
     */
    public static function toHex(OklchColor $color): string
    {
        return self::channelsToHex(self::toSrgb(self::inGamut($color)));
    }

    /**
     * The same color with chroma lowered just enough to fit sRGB (or the
     * color itself if it already fits).
     */
    public static function inGamut(OklchColor $color): OklchColor
    {
        if (self::fits($color)) {
            return $color;
        }

        // Binary search on the stored integer chroma: the largest that fits.
        $low = 0;
        $high = $color->chroma;

        while ($low < $high) {
            $mid = intdiv($low + $high + 1, 2);

            if (self::fits($color->withChroma($mid))) {
                $low = $mid;
            } else {
                $high = $mid - 1;
            }
        }

        return $color->withChroma($low);
    }

    public static function fits(OklchColor $color): bool
    {
        foreach (self::toLinearSrgb($color) as $channel) {
            if ($channel < -self::GAMUT_EPSILON || $channel > 1 + self::GAMUT_EPSILON) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{float, float, float} linear sRGB, possibly outside 0-1
     */
    private static function toLinearSrgb(OklchColor $color): array
    {
        $hue = deg2rad($color->h());
        $okL = $color->l();
        $okA = $color->c() * cos($hue);
        $okB = $color->c() * sin($hue);

        $l = ($okL + 0.3963377774 * $okA + 0.2158037573 * $okB) ** 3;
        $m = ($okL - 0.1055613458 * $okA - 0.0638541728 * $okB) ** 3;
        $s = ($okL - 0.0894841775 * $okA - 1.2914855480 * $okB) ** 3;

        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];
    }

    /**
     * @return array{float, float, float} gamma-encoded sRGB, 0-1
     */
    private static function toSrgb(OklchColor $color): array
    {
        [$r, $g, $b] = self::toLinearSrgb($color);

        return [self::fromLinear($r), self::fromLinear($g), self::fromLinear($b)];
    }

    private static function toLinear(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    private static function fromLinear(float $c): float
    {
        $c = max(0.0, min(1.0, $c));

        return $c <= 0.0031308 ? 12.92 * $c : 1.055 * ($c ** (1 / 2.4)) - 0.055;
    }

    private static function cbrt(float $x): float
    {
        return $x < 0 ? -((-$x) ** (1 / 3)) : $x ** (1 / 3);
    }

    /**
     * @return array{float, float, float}
     */
    private static function hexToChannels(string $hex): array
    {
        if (preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $m) !== 1) {
            throw new InvalidArgumentException("Not a #rrggbb color: {$hex}");
        }

        return [hexdec($m[1]) / 255, hexdec($m[2]) / 255, hexdec($m[3]) / 255];
    }

    /**
     * @param  array{float, float, float}  $channels
     */
    private static function channelsToHex(array $channels): string
    {
        return '#'.implode('', array_map(
            fn (float $c) => str_pad(dechex((int) round(max(0.0, min(1.0, $c)) * 255)), 2, '0', STR_PAD_LEFT),
            $channels,
        ));
    }
}
