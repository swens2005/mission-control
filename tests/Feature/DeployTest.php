<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

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

test('reports only the exception type when migrations throw', function () {
    Artisan::shouldReceive('call')->once()->andThrow(new RuntimeException('secret host details'));

    $this->withToken('correct-token')
        ->postJson(route('deploy.migrate'))
        ->assertStatus(500)
        ->assertExactJson(['ok' => false, 'error' => 'RuntimeException', 'code' => null]);
});

test('reports the numeric driver code, not the message, for database errors', function () {
    $pdo = new PDOException("SQLSTATE[HY000] [1045] Access denied for user 'secret-user'");
    $pdo->errorInfo = ['HY000', 1045, "Access denied for user 'secret-user'"];

    Artisan::shouldReceive('call')->once()->andThrow(
        new QueryException('mariadb', 'select 1', [], $pdo),
    );

    $this->withToken('correct-token')
        ->postJson(route('deploy.migrate'))
        ->assertStatus(500)
        ->assertExactJson(['ok' => false, 'error' => 'QueryException', 'code' => 1045])
        ->assertDontSee('secret-user');
});

test('works on a fresh database whose cache table does not exist yet', function () {
    // Production's default cache store is the database. The rate limiter must
    // not depend on it, or the first deploy can never run its migrations.
    config(['cache.default' => 'database']);
    Schema::drop('cache');

    $this->withToken('correct-token')
        ->postJson(route('deploy.migrate'))
        ->assertOk();
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
