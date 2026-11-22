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

test('every page has a share image with its title, size and alt text (story 48)', function (string $url) {
    $image = asset('images/og.jpg');

    $this->get($url)
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Mission Control', false)
        ->assertSee('<meta property="og:description" content="Mission Control runs a small web studio', false)
        ->assertSee('<meta property="og:image" content="'.$image.'">', false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertSee('<meta property="og:image:height" content="630">', false)
        ->assertSee('<meta property="og:image:alt" content="', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
})->with([
    'login' => '/login',
    'autopilot' => '/autopilot',
]);

test('the share image exists at 1200 by 630', function () {
    $size = getimagesize(public_path('images/og.jpg'));

    expect($size)->not->toBeFalse()
        ->and([$size[0], $size[1], $size['mime']])->toBe([1200, 630, 'image/jpeg']);
});
