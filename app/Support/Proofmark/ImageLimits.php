<?php

namespace App\Support\Proofmark;

/**
 * What Proofmark accepts, checked from the image header before any pixels
 * are decoded (ADR 0009).
 */
final class ImageLimits
{
    public const MAX_BYTES = 8 * 1024 * 1024;

    public const MAX_PIXELS = 16_000_000;

    public const MAX_SIDE = 10_000;

    /** Wider images are scaled down to this width. */
    public const MAX_WIDTH = 2560;

    /**
     * Why an image of this size is refused, or null if it's fine.
     */
    public static function problem(int $width, int $height): ?string
    {
        if ($width < 1 || $height < 1) {
            return 'This image has no size. Try exporting it again.';
        }

        if (max($width, $height) > self::MAX_SIDE) {
            return 'This image is more than '.number_format(self::MAX_SIDE).' pixels on one side. Export it smaller.';
        }

        if ($width * $height > self::MAX_PIXELS) {
            return 'This image has more than 16 megapixels. Export it smaller, for example at 1x.';
        }

        return null;
    }

    /**
     * The stored size: unchanged up to MAX_WIDTH, otherwise scaled down to
     * it with the same proportions (at least 1 px tall).
     *
     * @return array{int, int}
     */
    public static function storedSize(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH) {
            return [$width, $height];
        }

        return [self::MAX_WIDTH, max(1, intdiv($height * self::MAX_WIDTH + intdiv($width, 2), $width))];
    }
}
