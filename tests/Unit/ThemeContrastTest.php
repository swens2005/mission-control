<?php

use App\Support\Color\Contrast;

/**
 * Reads the portal tokens straight from resources/css/themes.css, the single
 * source of the colors, so the CSS can't drift from what is tested.
 *
 * @return array<string, array<string, string>> portal => [token => #hex]
 */
function themeTokens(): array
{
    $css = (string) file_get_contents(dirname(__DIR__, 2).'/resources/css/themes.css');
    preg_match_all("/\[data-portal='(\w+)'\][^{]*\{([^}]*)\}/", $css, $blocks, PREG_SET_ORDER);

    $themes = [];

    foreach ($blocks as [, $portal, $body]) {
        preg_match_all('/--([\w-]+):\s*(#[0-9a-f]{6})\s*;/i', $body, $tokens, PREG_SET_ORDER);

        foreach ($tokens as [, $name, $hex]) {
            $themes[$portal][$name] = strtolower($hex);
        }
    }

    return $themes;
}

/**
 * [text token, background token, minimum ratio]. Text pairs need 4.5:1;
 * input borders, focus rings and other UI parts need 3:1 (WCAG 1.4.11).
 */
dataset('theme pairs', function () {
    $text = [
        ['foreground', 'background'],
        ['foreground', 'card'],
        ['card-foreground', 'card'],
        ['popover-foreground', 'popover'],
        ['muted-foreground', 'background'],
        ['muted-foreground', 'card'],
        ['muted-foreground', 'muted'],
        ['primary', 'background'],
        ['primary', 'card'],
        ['primary-foreground', 'primary'],
        ['secondary-foreground', 'secondary'],
        ['accent-foreground', 'accent'],
        ['destructive', 'background'],
        ['destructive', 'card'],
        ['sidebar-foreground', 'sidebar'],
        ['sidebar-muted', 'sidebar'],
        ['sidebar-accent-foreground', 'sidebar-accent'],
        ['sidebar-primary-foreground', 'sidebar-primary'],
    ];

    $ui = [
        ['input', 'background'],
        ['input', 'card'],
        ['ring', 'background'],
        ['ring', 'card'],
        ['sidebar-ring', 'sidebar'],
    ];

    foreach (['admin', 'client'] as $portal) {
        foreach ($text as [$fg, $bg]) {
            yield "{$portal}: {$fg} on {$bg}" => [$portal, $fg, $bg, Contrast::AA_TEXT];
        }
        foreach ($ui as [$fg, $bg]) {
            yield "{$portal}: {$fg} against {$bg}" => [$portal, $fg, $bg, Contrast::AA_LARGE];
        }
    }
});

test('theme color pairs meet WCAG 2.2 AA', function (string $portal, string $fg, string $bg, float $minimum) {
    $theme = themeTokens()[$portal] ?? [];

    expect($theme)->toHaveKeys([$fg, $bg]);

    $ratio = Contrast::ratio($theme[$fg], $theme[$bg]);

    expect($ratio)->toBeGreaterThanOrEqual(
        $minimum,
        sprintf('%s %s on %s is %.2f:1, needs %.1f:1', $portal, $fg, $bg, $ratio, $minimum),
    );
})->with('theme pairs');

test('ink text on the riso pink shape color passes AA', function () {
    $client = themeTokens()['client'];

    expect(Contrast::ratio($client['foreground'], $client['brand-accent']))
        ->toBeGreaterThanOrEqual(Contrast::AA_TEXT);
});

test('the client theme defines every admin token', function () {
    $themes = themeTokens();

    expect(array_diff(array_keys($themes['admin']), array_keys($themes['client'])))->toBeEmpty();
});
