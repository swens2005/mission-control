<?php

namespace App\Support\Color;

use InvalidArgumentException;

/**
 * An OKLCH color as stored: integers only (story 20).
 *
 * - lightness in hundredths of a percent: 0 to 10 000 (L = 0 to 1);
 * - chroma in ten-thousandths: 0 to 4 000 (C = 0 to 0.4);
 * - hue in hundredths of a degree: 0 to 35 999.
 */
final readonly class OklchColor
{
    public const MAX_LIGHTNESS = 10_000;

    public const MAX_CHROMA = 4_000;

    public const HUE_TURN = 36_000;

    public function __construct(
        public int $lightness,
        public int $chroma,
        public int $hue,
    ) {
        if ($lightness < 0 || $lightness > self::MAX_LIGHTNESS) {
            throw new InvalidArgumentException("Lightness out of range: {$lightness}");
        }

        if ($chroma < 0 || $chroma > self::MAX_CHROMA) {
            throw new InvalidArgumentException("Chroma out of range: {$chroma}");
        }

        if ($hue < 0 || $hue >= self::HUE_TURN) {
            throw new InvalidArgumentException("Hue out of range: {$hue}");
        }
    }

    /**
     * From real numbers (L 0-1, C 0-0.4, H in degrees), rounded to the
     * stored precision and clamped into range.
     */
    public static function fromFloats(float $l, float $c, float $h): self
    {
        $hue = (int) round($h * 100) % self::HUE_TURN;

        return new self(
            max(0, min(self::MAX_LIGHTNESS, (int) round($l * 10_000))),
            max(0, min(self::MAX_CHROMA, (int) round($c * 10_000))),
            $hue < 0 ? $hue + self::HUE_TURN : $hue,
        );
    }

    public function l(): float
    {
        return $this->lightness / 10_000;
    }

    public function c(): float
    {
        return $this->chroma / 10_000;
    }

    public function h(): float
    {
        return $this->hue / 100;
    }

    public function withLightness(int $lightness): self
    {
        return new self($lightness, $this->chroma, $this->hue);
    }

    public function withChroma(int $chroma): self
    {
        return new self($this->lightness, $chroma, $this->hue);
    }

    /**
     * CSS notation, e.g. "oklch(27.12% 0.051 255.3)".
     */
    public function css(): string
    {
        return sprintf(
            'oklch(%s%% %s %s)',
            rtrim(rtrim(number_format($this->lightness / 100, 2, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format($this->chroma / 10_000, 3, '.', ''), '0'), '.') ?: '0',
            rtrim(rtrim(number_format($this->hue / 100, 1, '.', ''), '0'), '.'),
        );
    }
}
