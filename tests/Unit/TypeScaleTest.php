<?php

use App\Support\Typography\TypeScale;

test('a major third on 16 px', function () {
    expect(TypeScale::steps(16, 1250, 5, 2))->toBe([
        ['name' => 'xs', 'px' => 10, 'rem' => '0.625rem'],
        ['name' => 'sm', 'px' => 13, 'rem' => '0.8125rem'],
        ['name' => 'base', 'px' => 16, 'rem' => '1rem'],
        ['name' => 'lg', 'px' => 20, 'rem' => '1.25rem'],
        ['name' => 'xl', 'px' => 25, 'rem' => '1.5625rem'],
        ['name' => '2xl', 'px' => 31, 'rem' => '1.9375rem'],
        ['name' => '3xl', 'px' => 39, 'rem' => '2.4375rem'],
        ['name' => '4xl', 'px' => 49, 'rem' => '3.0625rem'],
    ]);
});

test('each named ratio grows as expected', function (int $ratio, int $largest) {
    $steps = TypeScale::steps(16, $ratio, 4, 0);

    expect(end($steps)['px'])->toBe($largest)
        ->and($steps[0]['name'])->toBe('base');
})->with([
    'minor third' => [1200, 33],
    'major third' => [1250, 39],
    'perfect fourth' => [1333, 51],
    'golden ratio' => [1618, 110],
]);

test('sizes round to whole pixels', function () {
    // 18 * 1.333 = 23.994 rounds to 24; 18 / 1.333 = 13.5 rounds to 14.
    expect(TypeScale::size(18, 1333, 1))->toBe(24)
        ->and(TypeScale::size(18, 1333, -1))->toBe(14)
        ->and(TypeScale::size(18, 1333, 0))->toBe(18);
});

test('scales that would be unusable are refused', function (array $args, string $message) {
    expect(TypeScale::problem(...$args))->toContain($message);
})->with([
    'tiny base' => [[8, 1250, 3, 0], 'base size'],
    'huge base' => [[40, 1250, 3, 0], 'base size'],
    'flat ratio' => [[16, 1000, 3, 0], 'ratio'],
    'wild ratio' => [[16, 3000, 3, 0], 'ratio'],
    'no steps up' => [[16, 1250, 0, 0], 'steps up'],
    'too many down' => [[16, 1250, 3, 4], 'steps down'],
    // 24 * 1.618^8 is about 1124 px.
    'giant top size' => [[24, 1618, 8, 0], 'largest size would be'],
    // 12 / 1.618^3 is under 3 px.
    'microscopic bottom' => [[12, 1618, 2, 3], 'smallest size'],
]);

test('steps() throws for an unusable scale', function () {
    expect(fn () => TypeScale::steps(24, 2000, 8, 0))->toThrow(InvalidArgumentException::class);
});

test('rem values are trimmed', function () {
    expect(TypeScale::rem(16))->toBe('1rem')
        ->and(TypeScale::rem(24))->toBe('1.5rem')
        ->and(TypeScale::rem(13))->toBe('0.8125rem');
});

test('typed ratios become thousandths without floats', function (string $typed, ?int $ratio) {
    expect(TypeScale::parseRatio($typed))->toBe($ratio);
})->with([
    ['1.25', 1250],
    ['1.333', 1333],
    ['1.5', 1500],
    [' 2 ', 2000],
    ['1.2345', null],
    ['1,25', null],
    ['abc', null],
    ['', null],
]);

test('ratios format back for people', function () {
    expect(TypeScale::formatRatio(1250))->toBe('1.25')
        ->and(TypeScale::formatRatio(2000))->toBe('2')
        ->and(TypeScale::formatRatio(1618))->toBe('1.618');
});
