<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * studio:create, the one-off command for a real studio (story 38, ADR 0012).
 */

function createStudio(
    string $email = 'meagan@example.com',
    string $password = 'Launch-pad-2026!',
    ?string $again = null,
): PendingCommand {
    return test()->artisan('studio:create')
        ->expectsQuestion('Studio name', 'Codelaunch Studio')
        ->expectsQuestion('Your name', 'Meagan Swenson')
        ->expectsQuestion('Your email address', $email)
        ->expectsQuestion('Password (hidden)', $password)
        ->expectsQuestion('Password again', $again ?? $password);
}

test('it creates a real workspace and its first admin', function () {
    createStudio(email: ' Meagan@Example.com ')
        ->expectsOutput('Created "Codelaunch Studio". Log in as meagan@example.com.')
        ->assertSuccessful();

    $workspace = Workspace::where('name', 'Codelaunch Studio')->sole();
    $admin = User::withoutGlobalScopes()->where('email', 'meagan@example.com')->sole();

    expect($workspace->is_sandbox)->toBeFalse()
        ->and($workspace->expires_at)->toBeNull()
        ->and($admin->workspace_id)->toBe($workspace->id)
        ->and($admin->role)->toBe(Role::Admin)
        ->and($admin->organization_id)->toBeNull()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Launch-pad-2026!', $admin->password))->toBeTrue();
});

test('the new admin can log in and sees an empty studio, not a sandbox', function () {
    createStudio()->assertSuccessful();

    $this->post('/login', ['email' => 'meagan@example.com', 'password' => 'Launch-pad-2026!'])
        ->assertRedirect('/admin');

    $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('workspace.name', 'Codelaunch Studio')
        ->where('workspace.isSandbox', false)
        ->where('workspace.expiresAt', null));
});

test('it refuses an email address that is already in use', function () {
    User::factory()->admin()->create(['email' => 'meagan@example.com']);

    createStudio()
        ->expectsOutput('The email has already been taken.')
        ->expectsOutput('Nothing was created. Run the command again.')
        ->assertFailed();

    expect(Workspace::where('name', 'Codelaunch Studio')->exists())->toBeFalse();
});

test('it refuses the demo sandbox email domain', function () {
    createStudio(email: 'meagan@sandbox.codelaunch.nl')
        ->expectsOutput('That domain is for demo sandboxes; use your own email address.')
        ->assertFailed();
});

test('it refuses passwords that do not match', function () {
    createStudio(again: 'Something-else-2026!')
        ->expectsOutput('The password field confirmation does not match.')
        ->assertFailed();

    expect(User::withoutGlobalScopes()->where('email', 'meagan@example.com')->exists())->toBeFalse();
});

test('in production it refuses a weak password', function () {
    app()->detectEnvironment(fn () => 'production');

    createStudio(password: 'short')->assertFailed();

    expect(Workspace::where('name', 'Codelaunch Studio')->exists())->toBeFalse();
});

test('it never prints the password', function () {
    createStudio()
        ->doesntExpectOutputToContain('Launch-pad-2026!')
        ->assertSuccessful();
});

test('sandbox:prune leaves a real studio alone', function () {
    createStudio()->assertSuccessful();

    $this->travel(30)->days();
    $this->artisan('sandbox:prune')->assertSuccessful();

    expect(Workspace::where('name', 'Codelaunch Studio')->exists())->toBeTrue();
});
