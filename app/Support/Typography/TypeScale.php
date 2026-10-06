<?php

namespace App\Support\Typography;

use InvalidArgumentException;

/**
 * A modular type scale (story 22): each step is the previous one times the
 * ratio. Sizes are whole pixels, with rem for a 16 px root.
 */
final class TypeScale
{
    /** Named ratios, in thousandths. */
    public const RATIOS = [
        1200 => 'Minor third (1.2)',
        1250 => 'Major third (1.25)',
        1333 => 'Perfect fourth (1.333)',
        1618 => 'Golden ratio (1.618)',
    ];

    public const MIN_RATIO = 1050;

    public const MAX_RATIO = 2000;

    public const MIN_BASE = 12;

    public const MAX_BASE = 24;

    public const MAX_UP = 8;

    public const MAX_DOWN = 3;

    /** Nothing in a scale may be larger than this, or smaller than MIN_SIZE. */
    public const MAX_SIZE = 160;

    public const MIN_SIZE = 8;

    private const UP = ['lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'];

    private const DOWN = ['sm', 'xs', '2xs'];

    /**
     * Steps from smallest to largest, the base named "base".
     *
     * @return list<array{name: string, px: int, rem: string}>
     */
    public static function steps(int $basePx, int $ratio, int $up, int $down): array
    {
        if ($problem = self::problem($basePx, $ratio, $up, $down)) {
            throw new InvalidArgumentException($problem);
        }

        $steps = [];

        for ($n = -$down; $n <= $up; $n++) {
            $steps[] = [
                'name' => match (true) {
                    $n === 0 => 'base',
                    $n > 0 => self::UP[$n - 1],
                    default => self::DOWN[-$n - 1],
                },
                'px' => self::size($basePx, $ratio, $n),
                'rem' => self::rem(self::size($basePx, $ratio, $n)),
            ];
        }

        return $steps;
    }

    /**
     * Why these settings make no usable scale, or null if they're fine.
     */
    public static function problem(int $basePx, int $ratio, int $up, int $down): ?string
    {
        return match (true) {
            $basePx < self::MIN_BASE || $basePx > self::MAX_BASE => 'The base size must be between '.self::MIN_BASE.' and '.self::MAX_BASE.' px.',
            $ratio < self::MIN_RATIO || $ratio > self::MAX_RATIO => 'The ratio must be between 1.05 and 2.',
            $up < 1 || $up > self::MAX_UP => 'Use 1 to '.self::MAX_UP.' steps up.',
            $down < 0 || $down > self::MAX_DOWN => 'Use 0 to '.self::MAX_DOWN.' steps down.',
            self::size($basePx, $ratio, $up) > self::MAX_SIZE => 'The largest size would be '.self::size($basePx, $ratio, $up).' px; keep it at '.self::MAX_SIZE.' px or less with fewer steps or a smaller ratio.',
            self::size($basePx, $ratio, -$down) < self::MIN_SIZE => 'The smallest size would be under '.self::MIN_SIZE.' px; use fewer steps down.',
            default => null,
        };
    }

    public static function size(int $basePx, int $ratio, int $step): int
    {
        return (int) round($basePx * (($ratio / 1000) ** $step));
    }

    /**
     * "1.25rem", trimmed: 16 px is "1rem", 13 px is "0.8125rem".
     */
    public static function rem(int $px): string
    {
        return rtrim(rtrim(number_format($px / 16, 4, '.', ''), '0'), '.').'rem';
    }

    /**
     * "1.333" typed by a person as integer thousandths, without floats; null
     * if it isn't a number with at most three decimals.
     */
    public static function parseRatio(string $value): ?int
    {
        if (preg_match('/^\s*(\d)(?:\.(\d{1,3}))?\s*$/', $value, $m) !== 1) {
            return null;
        }

        return (int) $m[1] * 1000 + (int) str_pad($m[2] ?? '', 3, '0');
    }

    /**
     * 1250 as "1.25".
     */
    public static function formatRatio(int $ratio): string
    {
        return rtrim(rtrim(number_format($ratio / 1000, 3, '.', ''), '0'), '.');
    }
}
