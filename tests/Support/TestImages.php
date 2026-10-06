<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Real images, made with GD, for Proofmark's upload tests.
 */
final class TestImages
{
    public static function bytes(string $type = 'png', int $width = 40, int $height = 30): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 138, 200, 0));

        ob_start();
        match ($type) {
            'jpg' => imagejpeg($image),
            'webp' => imagewebp($image),
            'gif' => imagegif($image),
            default => imagepng($image),
        };

        return (string) ob_get_clean();
    }

    public static function upload(string $type = 'png', int $width = 40, int $height = 30, ?string $name = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name ?? "design.{$type}", self::bytes($type, $width, $height));
    }

    /**
     * A JPEG with an EXIF block (holding a fake GPS position) after the
     * start marker, and a PHP snippet appended after the image data.
     */
    public static function jpegWithSecrets(): string
    {
        $jpeg = self::bytes('jpg');
        $exif = "Exif\0\0GPSLatitude 52.3676 SECRET-GPS-MARKER";
        $segment = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        return substr($jpeg, 0, 2).$segment.substr($jpeg, 2).'<?php echo "SECRET-PHP-MARKER"; ?>';
    }

    /**
     * A PNG header that claims a huge size, with no real pixel data.
     */
    public static function pngClaiming(int $width, int $height): string
    {
        $ihdr = 'IHDR'.pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1A\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
    }
}
