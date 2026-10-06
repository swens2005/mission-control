<?php

namespace App\Support\Color;

/**
 * What a person typed into a color field: hex (#rgb or #rrggbb) or CSS
 * oklch(L C H), with L as 0-1 or a percentage and an optional "deg".
 */
final readonly class ColorInput
{
    private function __construct(
        public string $hex,
        public OklchColor $oklch,
        /** The OKLCH value was outside sRGB and its chroma was lowered. */
        public bool $adjusted,
    ) {}

    /**
     * A computed color (from "Fix it"), already in gamut.
     */
    public static function fromOklch(OklchColor $color): self
    {
        $fitted = Oklch::inGamut($color);

        return new self(Oklch::toHex($fitted), $fitted, $fitted->chroma !== $color->chroma);
    }

    public static function parse(string $value): ?self
    {
        $value = strtolower(trim($value));

        if (preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/', $value, $m) === 1) {
            $hex = strlen($m[1]) === 3
                ? '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2]
                : '#'.$m[1];

            return new self($hex, Oklch::fromHex($hex), false);
        }

        $number = '(\d+(?:\.\d+)?|\.\d+)';

        if (preg_match("/^oklch\\(\\s*{$number}(%?)\\s+{$number}\\s+{$number}(?:deg)?\\s*\\)$/", $value, $m) !== 1) {
            return null;
        }

        $l = (float) $m[1] / ($m[2] === '%' ? 100 : 1);
        $c = (float) $m[3];
        $h = (float) $m[4];

        if ($l > 1 || $c > 0.4 || $h > 360) {
            return null;
        }

        $typed = OklchColor::fromFloats($l, $c, $h);
        $fitted = Oklch::inGamut($typed);

        return new self(Oklch::toHex($fitted), $fitted, $fitted->chroma !== $typed->chroma);
    }
}
