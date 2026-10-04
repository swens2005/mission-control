<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;

function cspOf(TestResponse $response): string
{
    return (string) $response->headers->get('Content-Security-Policy');
}

test('HTML pages get a strict CSP with a nonce', function () {
    $csp = cspOf($this->get(route('login')));

    expect($csp)
        ->toContain("default-src 'self'")
        ->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9]+'(;|$)/")
        ->toContain("object-src 'none'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'")
        ->toContain("frame-ancestors 'none'")
        ->not->toContain('unsafe-inline')
        ->not->toContain('unsafe-eval');
});

test('the nonce in the header is the one on the page\'s script tags', function () {
    $this->withVite();

    $response = $this->get(route('login'));

    preg_match("/'nonce-([A-Za-z0-9]+)'/", cspOf($response), $match);

    expect($match[1] ?? null)->not->toBeNull();
    $response->assertSee('nonce="'.$match[1].'"', false);
})->skip(fn () => ! is_file(public_path('build/manifest.json')), 'Needs a production build (CI builds first).');

test('every request gets a fresh nonce', function () {
    preg_match("/'nonce-([^']+)'/", cspOf($this->get(route('login'))), $first);
    preg_match("/'nonce-([^']+)'/", cspOf($this->get(route('login'))), $second);

    expect($first[1])->not->toBe($second[1]);
});

test('signed-in pages in both portals get the CSP', function () {
    expect(cspOf($this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))))
        ->toContain("default-src 'self'");

    expect(cspOf($this->actingAs(User::factory()->client()->create())->get(route('client.home'))))
        ->toContain("default-src 'self'");
});

test('JSON responses carry no CSP', function () {
    config(['app.deploy_token' => 'token']);
    $this->withToken('token')->postJson(route('deploy.migrate'))->assertHeaderMissing('Content-Security-Policy');
});

test('session cookies are HttpOnly, SameSite=Lax and scoped to the app path', function () {
    config(['session.path' => '/mission-control', 'session.secure' => true]);

    $cookie = collect($this->get(route('login'))->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and($cookie->getPath())->toBe('/mission-control');
});
