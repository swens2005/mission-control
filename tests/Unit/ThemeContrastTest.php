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
        ['status-pass', 'background'],
        ['status-pass', 'card'],
        ['status-warn', 'background'],
        ['status-warn', 'card'],
        ['status-fail', 'background'],
        ['status-fail', 'card'],
        ['screen-ink', 'screen-top'],
        ['screen-ink', 'screen-bottom'],
        ['screen-text', 'screen-bottom'],
        ['screen-label', 'screen-top'],
        ['screen-label', 'screen-bottom'],
        ['screen-status', 'screen-top'],
        ['screen-status', 'screen-bottom'],
        ['foreground', 'console'],
        ['foreground', 'sky'],
        ['muted-foreground', 'sky'],
        ['primary', 'sky'],
        ['destructive', 'sky'],
        ['muted-foreground', 'console'],
        ['sidebar-foreground', 'sidebar'],
        ['sidebar-muted', 'sidebar'],
        ['sidebar-accent-foreground', 'sidebar-accent'],
        ['sidebar-primary-foreground', 'sidebar-primary'],
        // Navigator's star-chart panel (story 42).
        ['nav-text', 'nav-deep'],
        ['nav-text', 'nav-bay'],
        ['nav-text', 'nav-bay-top'],
        ['nav-text', 'nav-field'],
        ['nav-text', 'nav-field-bottom'],
        ['nav-soft', 'nav-deep'],
        ['nav-soft', 'nav-bay'],
        ['nav-soft', 'nav-bay-top'],
        ['nav-soft', 'nav-field-bottom'],
        ['nav-violet', 'nav-deep'],
        ['nav-violet', 'nav-bay-top'],
        ['nav-violet-soft', 'nav-field-bottom'],
        ['nav-brass', 'nav-deep'],
        ['nav-brass', 'sidebar'],
        ['nav-go', 'nav-bay-top'],
        ['nav-amber', 'nav-deep'],
        ['nav-coral', 'nav-deep'],
        ['nav-coral', 'nav-bay-top'],
        // Dark ink on lamp-colored buttons (lc-pill, Navigator's button).
        ['ink', 'nav-button'],
        ['ink', 'brand-accent'],
        ['ink', 'lamp-go'],
    ];

    $ui = [
        ['nav-field-rim', 'nav-bay-top'],
        ['nav-field-rim', 'nav-deep'],
        ['nav-brass', 'nav-bay-top'],
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

test('the light portals keep ink-colored text on the brand accent', function (string $portal) {
    $theme = themeTokens()[$portal];

    expect(Contrast::ratio($theme['foreground'], $theme['brand-accent']))
        ->toBeGreaterThanOrEqual(Contrast::AA_TEXT);
})->with(['client', 'guest']);

test('the control room is dark: light text on a dark page', function () {
    $admin = themeTokens()['admin'];

    expect(Contrast::ratio($admin['background'], '#000000'))->toBeLessThan(2.0)
        ->and(Contrast::ratio($admin['foreground'], $admin['background']))->toBeGreaterThanOrEqual(Contrast::AAA_TEXT);
});

test('the client theme defines every admin token', function () {
    $themes = themeTokens();

    expect(array_diff(array_keys($themes['admin']), array_keys($themes['client'])))->toBeEmpty();
});

test('the viewing stand keeps text readable down the whole sky', function (string $fg, string $bg) {
    $client = themeTokens()['client'];

    expect(Contrast::ratio($client[$fg], $client[$bg]))->toBeGreaterThanOrEqual(Contrast::AA_TEXT);
})->with([
    ['foreground', 'sky-2'],
    ['muted-foreground', 'sky-2'],
    ['primary', 'sky-2'],
    ['foreground', 'card'],
]);

test('the viewing stand focus ring shows on the sky', function () {
    $client = themeTokens()['client'];

    expect(Contrast::ratio($client['ring'], $client['sky']))->toBeGreaterThanOrEqual(Contrast::AA_LARGE)
        ->and(Contrast::ratio($client['ring'], $client['sky-2']))->toBeGreaterThanOrEqual(Contrast::AA_LARGE);
});
