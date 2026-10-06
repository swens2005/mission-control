<?php

namespace App\Support\Palette;

use App\Support\Color\Contrast;

/**
 * A matrix cell's verdict (story 21). Text and accents get WCAG text
 * grades; shape-only colors are judged as graphics (WCAG 1.4.11), where
 * below 3:1 they can only be decoration.
 */
enum ContrastGrade: string
{
    case Aaa = 'aaa';
    case Aa = 'aa';
    case AaLarge = 'aa_large';
    case Fails = 'fails';
    case Graphics = 'graphics';
    case Decoration = 'decoration';

    public static function forText(float $ratio): self
    {
        return match (true) {
            $ratio >= Contrast::AAA_TEXT => self::Aaa,
            $ratio >= Contrast::AA_TEXT => self::Aa,
            $ratio >= Contrast::AA_LARGE => self::AaLarge,
            default => self::Fails,
        };
    }

    public static function forShape(float $ratio): self
    {
        return $ratio >= Contrast::AA_LARGE ? self::Graphics : self::Decoration;
    }

    public function label(): string
    {
        return match ($this) {
            self::Aaa => 'AAA',
            self::Aa => 'AA',
            self::AaLarge => 'AA large only',
            self::Fails => 'Fails',
            self::Graphics => 'Icons OK',
            self::Decoration => 'Decoration only',
        };
    }

    /**
     * Fine for its role: text at AA, shapes as graphics. "AA large only"
     * and "Fails" need a fix for body text; decoration is a choice.
     */
    public function passes(): bool
    {
        return in_array($this, [self::Aaa, self::Aa, self::Graphics], true);
    }

    /**
     * Only text that fails AA gets "Fix it"; shapes are never text.
     */
    public function fixable(): bool
    {
        return in_array($this, [self::AaLarge, self::Fails], true);
    }

    /**
     * pass, warn or fail: which status color to use next to the label.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Aaa, self::Aa, self::Graphics => 'pass',
            self::AaLarge, self::Decoration => 'warn',
            self::Fails => 'fail',
        };
    }
}
