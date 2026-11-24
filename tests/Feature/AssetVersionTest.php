<?php

/*
 * Story 49: a tab left open across a deploy is told to reload. Inertia's
 * default version is the asset URL when one is set (production's subfolder),
 * which never changes; ours is the build's manifest.
 */

test('a request from an old build is told to reload, also with a subfolder asset URL', function () {
    config(['app.asset_url' => 'https://example.test/mission-control']);

    $this->get('/login', ['X-Inertia' => 'true', 'X-Inertia-Version' => 'the-build-before'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', url('/login'));
});

test('a request from the current build gets the page', function () {
    config(['app.asset_url' => 'https://example.test/mission-control']);

    $current = hash_file('xxh128', public_path('build/manifest.json'));

    $this->get('/login', ['X-Inertia' => 'true', 'X-Inertia-Version' => $current])
        ->assertOk()
        ->assertJsonPath('version', $current);
});
