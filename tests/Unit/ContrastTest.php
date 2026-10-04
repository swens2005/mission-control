<?php

use App\Support\Color\Contrast;

test('black on white is 21:1 and a color on itself is 1:1', function () {
    expect(Contrast::ratio('#000000', '#ffffff'))->toBe(21.0)
        ->and(Contrast::ratio('#4a7300', '#4a7300'))->toBe(1.0);
});

test('the ratio is symmetric', function () {
    expect(Contrast::ratio('#10233a', '#f5f2ea'))
        ->toBe(Contrast::ratio('#f5f2ea', '#10233a'));
});

test('matches known WCAG values', function (string $fg, string $bg, float $expected) {
    expect(round(Contrast::ratio($fg, $bg), 2))->toBe($expected);
})->with([
    'grey #767676 on white is the classic AA edge' => ['#767676', '#ffffff', 4.54],
    'lime fails as text on card' => ['#8ac800', '#fffdf8', 2.0],
    'riso teal fails AA on paper' => ['#00838a', '#f4efe6', 3.97],
]);

test('accepts colors without the hash and in upper case', function () {
    expect(Contrast::ratio('FFFFFF', '000000'))->toBe(21.0);
});

test('rejects anything that is not #rrggbb', function () {
    Contrast::ratio('#fff', '#000000');
})->throws(InvalidArgumentException::class);
