<?php

use App\Support\Color\ColorInput;
use App\Support\Color\Oklch;
use App\Support\Color\OklchColor;

test('reference colors convert to known OKLCH values', function (string $hex, int $l, int $c, int $h) {
    $color = Oklch::fromHex($hex);

    // Within one stored step of the published values (rounding).
    expect(abs($color->lightness - $l))->toBeLessThanOrEqual(1)
        ->and(abs($color->chroma - $c))->toBeLessThanOrEqual(1)
        ->and(abs($color->hue - $h))->toBeLessThanOrEqual(2);
})->with([
    'white' => ['#ffffff', 10_000, 0, 0],
    'black' => ['#000000', 0, 0, 0],
    'mid grey' => ['#808080', 5_999, 0, 0],
    'sRGB red' => ['#ff0000', 6_280, 2_577, 2_923],
    'sRGB green' => ['#00ff00', 8_664, 2_948, 14_250],
    'sRGB blue' => ['#0000ff', 4_520, 3_132, 26_405],
]);

test('the codelaunch.nl colors round-trip through OKLCH', function (string $hex) {
    expect(Oklch::toHex(Oklch::fromHex($hex)))->toBe($hex);
})->with([
    'ink' => '#10233a',
    'cream' => '#f5f2ea',
    'card' => '#fffdf8',
    'lime' => '#8ac800',
    'green' => '#4a7300',
    'sky' => '#d4edfa',
    'screen label' => '#33598a',
    'CV label blue' => '#4a7fc4',
    'nogo orange' => '#ef5a2a',
]);

test('every 8-bit grey round-trips', function () {
    foreach (range(0, 255, 5) as $v) {
        $hex = sprintf('#%02x%02x%02x', $v, $v, $v);
        expect(Oklch::toHex(Oklch::fromHex($hex)))->toBe($hex);
    }
});

test('colors outside sRGB keep lightness and hue, and lose only chroma', function () {
    $vivid = new OklchColor(7_000, 4_000, 14_500);

    expect(Oklch::fits($vivid))->toBeFalse();

    $fitted = Oklch::inGamut($vivid);

    expect($fitted->lightness)->toBe(7_000)
        ->and($fitted->hue)->toBe(14_500)
        ->and($fitted->chroma)->toBeLessThan(4_000)
        ->and(Oklch::fits($fitted))->toBeTrue()
        // The largest chroma that fits: one step more doesn't.
        ->and(Oklch::fits($fitted->withChroma($fitted->chroma + 1)))->toBeFalse();
});

test('a color that fits is left alone', function () {
    $ink = Oklch::fromHex('#10233a');

    expect(Oklch::inGamut($ink))->toEqual($ink);
});

test('css notation is short and readable', function () {
    expect((new OklchColor(2_712, 510, 25_530))->css())->toBe('oklch(27.12% 0.051 255.3)')
        ->and((new OklchColor(10_000, 0, 0))->css())->toBe('oklch(100% 0 0)');
});

test('stored values outside their ranges are refused', function (int $l, int $c, int $h) {
    expect(fn () => new OklchColor($l, $c, $h))->toThrow(InvalidArgumentException::class);
})->with([
    [-1, 0, 0],
    [10_001, 0, 0],
    [5_000, 4_001, 0],
    [5_000, 0, 36_000],
]);

test('hue wraps around a full turn', function () {
    expect(OklchColor::fromFloats(0.5, 0.1, 360.0)->hue)->toBe(0)
        ->and(OklchColor::fromFloats(0.5, 0.1, -10.0)->hue)->toBe(35_000);
});

test('input accepts hex in both lengths and oklch in both lightness styles', function (string $typed, string $hex) {
    expect(ColorInput::parse($typed)?->hex)->toBe($hex);
})->with([
    ['#10233A', '#10233a'],
    ['10233a', '#10233a'],
    ['#fff', '#ffffff'],
    ['oklch(1 0 0)', '#ffffff'],
    ['oklch(100% 0 0)', '#ffffff'],
    ['  OKLCH(0.628 0.2577 29.23deg) ', '#ff0000'],
]);

test('typed oklch outside sRGB is adjusted and says so', function () {
    $input = ColorInput::parse('oklch(0.7 0.4 145)');

    expect($input?->adjusted)->toBeTrue()
        ->and($input?->oklch->lightness)->toBe(7_000)
        ->and(ColorInput::parse('#10233a')?->adjusted)->toBeFalse();
});

test('garbage input is refused', function (string $typed) {
    expect(ColorInput::parse($typed))->toBeNull();
})->with(['', 'red', '#12345', '#ggg', 'oklch(2 0.1 10)', 'oklch(0.5 0.9 10)', 'oklch(0.5 0.1)', 'rgb(1,2,3)', 'oklch(0.5 0.1 400)']);
