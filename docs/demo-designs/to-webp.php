<?php

/*
 * Converts the rendered PNG mockups into the WebP files the demo ships
 * (story 19). Usage, from the repo root:
 *
 *     php docs/demo-designs/to-webp.php <folder with PNGs>
 *
 * Writes resources/demo/proofmark/<name>.webp at quality 80.
 */

$source = rtrim($argv[1] ?? '', '/\\');
$target = __DIR__.'/../../resources/demo/proofmark';

if ($source === '' || ! is_dir($source)) {
    fwrite(STDERR, "Usage: php docs/demo-designs/to-webp.php <folder with PNGs>\n");
    exit(1);
}

if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

$total = 0;

foreach (glob($source.'/*.png') ?: [] as $png) {
    $image = imagecreatefrompng($png);
    $out = $target.'/'.basename($png, '.png').'.webp';
    imagewebp($image, $out, 80);
    $bytes = (int) filesize($out);
    $total += $bytes;
    printf("%-34s %4d x %-5d %6.1f KB\n", basename($out), imagesx($image), imagesy($image), $bytes / 1024);
}

printf("Total: %.1f KB\n", $total / 1024);
