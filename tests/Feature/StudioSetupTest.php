<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Sandbox\SandboxFactory;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * The one-time setup page for the first real studio (story 41, ADR 0013).
 */

const SETUP_TOKEN = 'a-long-random-token-only-meagan-has-0123456789';

beforeEach(function () {
    config(['studio.setup_token_sha256' => hash('sha256', SETUP_TOKEN)]);
    RateLimiter::clear('setup');
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function setupForm(array $overrides = []): array
{
    return [
        'token' => SETUP_TOKEN,
        'studio' => 'Adroidea',
        'name' => 'Meagan Swenson',
        'email' => 'meagan@example.com',
        'password' => 'Launch-pad-2026!',
        'password_confirmation' => 'Launch-pad-2026!',
        ...$overrides,
    ];
}

test('the page is 404 when no token fingerprint is configured', function () {
    config(['studio.setup_token_sha256' => '']);

    $this->get('/setup')->assertNotFound();
    $this->post('/setup', setupForm())->assertNotFound();

    expect(Workspace::where('is_sandbox', false)->exists())->toBeFalse();
});

test('while setup is open, guests see the form', function () {
    $this->get('/setup')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/studio-setup'));
});

test('demo sandboxes do not close setup', function () {
    app(SandboxFactory::class)->create();

    $this->get('/setup')->assertOk();
});

test('the right token creates the studio, logs in and closes setup', function () {
    $this->post('/setup', setupForm(['email' => ' Meagan@Example.com ']))
        ->assertRedirect(route('admin.dashboard'));

    $admin = User::withoutGlobalScopes()->where('email', 'meagan@example.com')->sole();

    expect($admin->role)->toBe(Role::Admin)
        ->and($admin->workspace->name)->toBe('Adroidea')
        ->and($admin->workspace->is_sandbox)->toBeFalse()
        ->and($admin->workspace->expires_at)->toBeNull();

    $this->assertAuthenticatedAs($admin);

    // Closed for good, even for a guest with the right token.
    auth()->logout();
    $this->get('/setup')->assertNotFound();
    $this->post('/setup', setupForm(['email' => 'someone@example.com']))->assertNotFound();

    expect(Workspace::where('is_sandbox', false)->count())->toBe(1);
});

test('a wrong or missing token creates nothing and says so plainly', function (string $token) {
    $this->from('/setup')->post('/setup', setupForm(['token' => $token]))
        ->assertRedirect('/setup')
        ->assertSessionHasErrors(['token' => 'That setup token isn’t right.'])
        ->assertSessionDoesntHaveErrors(['email', 'password']);

    expect(Workspace::where('is_sandbox', false)->exists())->toBeFalse();
    $this->assertGuest();
})->with([
    'wrong' => 'not-the-token',
    'empty' => '',
]);

test('it applies the same rules as studio:create', function (array $overrides, string $field) {
    $this->from('/setup')->post('/setup', setupForm($overrides))
        ->assertSessionHasErrors($field);

    expect(Workspace::where('is_sandbox', false)->exists())->toBeFalse();
})->with([
    'no studio name' => [['studio' => ''], 'studio'],
    'not an email' => [['email' => 'meagan'], 'email'],
    'demo domain' => [['email' => 'meagan@sandbox.codelaunch.nl'], 'email'],
    'passwords differ' => [['password_confirmation' => 'Something-else-2026!'], 'password'],
]);

test('attempts are rate limited', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/setup', setupForm(['token' => 'guess-'.$attempt]))->assertRedirect();
    }

    $this->post('/setup', setupForm(['token' => 'guess-6']))->assertTooManyRequests();
});

test('signed-in users are sent to their portal', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/setup')
        ->assertRedirect();
});
