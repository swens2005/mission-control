<?php

use App\Support\Color\Contrast;
use App\Support\Color\ContrastFixer;
use App\Support\Color\Oklch;
use App\Support\Color\OklchColor;
use App\Support\Palette\ContrastGrade;

test('on a light surface the fix darkens, just enough', function () {
    // The CV site's screen label blue on the lower screen gradient (ADR 0008).
    $blue = Oklch::fromHex('#4a7fc4');
    $screen = '#cfe8fa';

    expect(Contrast::ratio('#4a7fc4', $screen))->toBeLessThan(4.5);

    $fixed = ContrastFixer::fix($blue, $screen, Contrast::AA_TEXT);

    expect($fixed)->not->toBeNull()
        ->and($fixed->lightness)->toBeLessThan($blue->lightness)
        ->and($fixed->hue)->toBe($blue->hue)
        ->and($fixed->chroma)->toBe($blue->chroma)
        ->and(ContrastFixer::ratio($fixed, $screen))->toBeGreaterThanOrEqual(4.5)
        // One stored step lighter would fail: it's the closest passing value.
        ->and(ContrastFixer::ratio($fixed->withLightness($fixed->lightness + 1), $screen))->toBeLessThan(4.5);
});

test('on a dark surface the fix lightens', function () {
    $grey = Oklch::fromHex('#4a5a6c');
    $ink = '#10233a';

    $fixed = ContrastFixer::fix($grey, $ink, Contrast::AA_TEXT);

    expect($fixed?->lightness)->toBeGreaterThan($grey->lightness)
        ->and(ContrastFixer::ratio($fixed, $ink))->toBeGreaterThanOrEqual(4.5)
        ->and(ContrastFixer::ratio($fixed->withLightness($fixed->lightness - 1), $ink))->toBeLessThan(4.5);
});

test('lime as text on cream gets the darker green', function () {
    $lime = Oklch::fromHex('#8ac800');
    $fixed = ContrastFixer::fix($lime, '#f5f2ea', Contrast::AA_TEXT);

    // Darker, same hue: a green like the brand's #4a7300.
    expect($fixed?->lightness)->toBeLessThan($lime->lightness)
        ->and(abs($fixed->hue - $lime->hue))->toBeLessThan(10)
        ->and(ContrastFixer::ratio($fixed, '#f5f2ea'))->toBeGreaterThanOrEqual(4.5);
});

test('a color that already passes comes back unchanged', function () {
    $ink = Oklch::fromHex('#10233a');

    expect(ContrastFixer::fix($ink, '#fffdf8', Contrast::AA_TEXT))->toEqual($ink);
});

test('when nothing passes it says so', function () {
    // 7:1 on mid grey is impossible: white and black both fall short.
    expect(ContrastFixer::fix(new OklchColor(5_000, 0, 0), '#777777', 7.0))->toBeNull();
});

test('the nearer direction wins', function () {
    // On mid grey, a dark grey is closer to passing by going darker.
    $fixed = ContrastFixer::fix(Oklch::fromHex('#555555'), '#909090', Contrast::AA_LARGE);

    expect($fixed?->lightness)->toBeLessThan(Oklch::fromHex('#555555')->lightness);
});

test('text grades follow WCAG thresholds', function (float $ratio, ContrastGrade $grade) {
    expect(ContrastGrade::forText($ratio))->toBe($grade);
})->with([
    [21.0, ContrastGrade::Aaa],
    [7.0, ContrastGrade::Aaa],
    [6.99, ContrastGrade::Aa],
    [4.5, ContrastGrade::Aa],
    [4.49, ContrastGrade::AaLarge],
    [3.0, ContrastGrade::AaLarge],
    [2.99, ContrastGrade::Fails],
]);

test('shapes are graded as graphics, never as text', function () {
    expect(ContrastGrade::forShape(3.0))->toBe(ContrastGrade::Graphics)
        ->and(ContrastGrade::forShape(1.8))->toBe(ContrastGrade::Decoration)
        ->and(ContrastGrade::Decoration->fixable())->toBeFalse()
        ->and(ContrastGrade::Decoration->passes())->toBeFalse()
        ->and(ContrastGrade::Fails->fixable())->toBeTrue();
});
