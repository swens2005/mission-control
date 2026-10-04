<?php

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config(['app.deploy_token' => 'correct-token']);
});

test('runs migrations with the right token', function () {
    $this->withToken('correct-token')
        ->postJson(route('deploy.migrate'))
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('reports failure when migrations fail', function () {
    Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('boom');

    $this->withToken('correct-token')
        ->postJson(route('deploy.migrate'))
        ->assertStatus(500)
        ->assertJson(['ok' => false, 'output' => 'boom']);
});

test('hides itself from a wrong or missing token', function (?string $token) {
    Artisan::shouldReceive('call')->never();

    $request = $token === null ? $this : $this->withToken($token);

    $request->postJson(route('deploy.migrate'))->assertNotFound();
})->with([
    'wrong token' => 'wrong-token',
    'no token' => null,
]);

test('is disabled when no token is configured', function () {
    config(['app.deploy_token' => '']);
    Artisan::shouldReceive('call')->never();

    $this->withToken('')->postJson(route('deploy.migrate'))->assertNotFound();
});

test('only accepts POST', function () {
    $this->withToken('correct-token')
        ->getJson(route('deploy.migrate'))
        ->assertMethodNotAllowed();
});

test('is rate limited', function () {
    foreach (range(1, 5) as $attempt) {
        $this->withToken('wrong-token')->postJson(route('deploy.migrate'))->assertNotFound();
    }

    $this->withToken('wrong-token')->postJson(route('deploy.migrate'))->assertTooManyRequests();
});

test('does not start a session or set cookies', function () {
    $this->withToken('correct-token')
        ->postJson(route('deploy.migrate'))
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'))
        ->assertCookieMissing('XSRF-TOKEN');
});
