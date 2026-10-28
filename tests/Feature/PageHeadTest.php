<?php

/*
 * What every page's <head> carries for search engines and fast first paint
 * (story 37).
 */

test('every page has a meta description', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertSee('<meta name="description" content="Mission Control runs a small web studio from first conversation to launch day', false);
})->with([
    'login' => '/login',
    'autopilot' => '/autopilot',
]);

test('the three Latin fonts are preloaded, so text does not jump when they arrive', function () {
    $html = $this->get('/login')->assertOk()->getContent();

    expect(substr_count((string) $html, 'as="font" type="font/woff2" crossorigin'))->toBe(3);
});

test('the login page and the autopilot can be indexed', function (string $url) {
    $this->get($url)
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertDontSee('noindex', false);
})->with([
    'login' => '/login',
    'autopilot' => '/autopilot',
]);
