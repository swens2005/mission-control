<?php

namespace App\Support\Proofmark;

use GdImage;

/**
 * Turns an untrusted upload into a clean image (ADR 0009):
 *
 * 1. recognise PNG, JPEG or WebP by the file's first bytes, and confirm
 *    with getimagesize (the name and browser MIME type are ignored);
 * 2. check the size limits from the header, before decoding pixels;
 * 3. decode with GD and write a brand-new file of the same type.
 *
 * Only the pixels survive step 3, so EXIF, GPS, comments and anything
 * hidden after the image data are dropped.
 */
final class ImageProcessor
{
    private const TYPES = [
        IMAGETYPE_PNG => ['image/png', 'png'],
        IMAGETYPE_JPEG => ['image/jpeg', 'jpg'],
        IMAGETYPE_WEBP => ['image/webp', 'webp'],
    ];

    /**
     * @throws RejectedImage
     */
    public function process(string $path): ProcessedImage
    {
        $size = @filesize($path);

        if ($size === false || $size === 0) {
            throw new RejectedImage('The file is empty or could not be read.');
        }

        if ($size > ImageLimits::MAX_BYTES) {
            throw new RejectedImage('The file is larger than 8 MB.');
        }

        $type = $this->sniff($path);
        $info = @getimagesize($path);

        if ($type === null || $info === false || $info[2] !== $type) {
            throw new RejectedImage('Upload a PNG, JPG or WebP image. Other files, including SVG and GIF, are not accepted.');
        }

        [$width, $height] = $info;

        if (($problem = ImageLimits::problem($width, $height)) !== null) {
            throw new RejectedImage($problem);
        }

        $image = $this->decode($path, $type);
        [$targetWidth, $targetHeight] = ImageLimits::storedSize($width, $height);

        if ($targetWidth !== $width) {
            $scaled = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC);

            if ($scaled === false) {
                throw new RejectedImage('This image could not be resized. Try exporting it smaller.');
            }

            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            $image = $scaled;
        }

        [$mime, $extension] = self::TYPES[$type];

        return new ProcessedImage(
            contents: $this->encode($image, $type),
            mime: $mime,
            extension: $extension,
            width: imagesx($image),
            height: imagesy($image),
        );
    }

    /**
     * The image type from its magic bytes, or null if it isn't one we take.
     */
    private function sniff(string $path): ?int
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $head = (string) fread($handle, 12);
        fclose($handle);

        return match (true) {
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => IMAGETYPE_PNG,
            str_starts_with($head, "\xFF\xD8\xFF") => IMAGETYPE_JPEG,
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP' => IMAGETYPE_WEBP,
            default => null,
        };
    }

    private function decode(string $path, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            default => @imagecreatefromwebp($path),
        };

        if (! $image instanceof GdImage) {
            throw new RejectedImage($type === IMAGETYPE_WEBP
                ? 'This WebP image could not be read. Animated WebP is not supported.'
                : 'This image could not be read. It may be damaged; try exporting it again.');
        }

        // Palette PNGs become true colour, so scaling and alpha behave.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    private function encode(GdImage $image, int $type): string
    {
        ob_start();

        $ok = match ($type) {
            IMAGETYPE_PNG => imagepng($image, null, 6),
            IMAGETYPE_JPEG => imagejpeg($image, null, 85),
            default => imagewebp($image, null, 85),
        };

        $contents = (string) ob_get_clean();

        if (! $ok || $contents === '') {
            throw new RejectedImage('This image could not be saved. Try exporting it again.');
        }

        return $contents;
    }
}
