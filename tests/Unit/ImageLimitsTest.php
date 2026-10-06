<?php

use App\Support\Proofmark\ImageLimits;

test('normal design sizes are accepted', function (int $width, int $height) {
    expect(ImageLimits::problem($width, $height))->toBeNull();
})->with([
    'desktop screen' => [1440, 900],
    'long page' => [1440, 9000],
    'exactly 16 MP' => [4000, 4000],
    'longest side at the limit' => [1600, 10_000],
]);

test('oversized images are refused before decoding', function (int $width, int $height, string $message) {
    expect(ImageLimits::problem($width, $height))->toContain($message);
})->with([
    'too tall' => [100, 10_001, 'pixels on one side'],
    'too wide' => [10_001, 100, 'pixels on one side'],
    'too many pixels' => [4001, 4000, '16 megapixels'],
    'decompression bomb' => [50_000, 50_000, 'pixels on one side'],
    'no size' => [0, 10, 'no size'],
]);

test('images up to 2560 px wide keep their size', function () {
    expect(ImageLimits::storedSize(2560, 4000))->toBe([2560, 4000])
        ->and(ImageLimits::storedSize(375, 812))->toBe([375, 812]);
});

test('wider images are scaled down to 2560 px with the same proportions', function (int $width, int $height, array $expected) {
    expect(ImageLimits::storedSize($width, $height))->toBe($expected);
})->with([
    'retina desktop' => [2880, 1800, [2560, 1600]],
    'rounds to the nearest pixel' => [3000, 1001, [2560, 854]],
    'very wide strip stays at least 1 px' => [10_000, 1, [2560, 1]],
]);
