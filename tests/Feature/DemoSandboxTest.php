<?php

use App\Enums\ProjectPhase;
use App\Enums\Role;
use App\Models\ActivityEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Sandbox\SandboxFactory;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function sandboxCredentials(): array
{
    test()->post(route('demo.store'))->assertRedirect(route('login'));

    return session('demo');
}

test('taking the controls creates a complete sandbox and pre-fills the login', function () {
    $credentials = sandboxCredentials();

    $workspace = Workspace::sole();
    $admin = User::where('email', $credentials['admin']['email'])->sole();
    $client = User::where('email', $credentials['client']['email'])->sole();

    expect($workspace->is_sandbox)->toBeTrue()
        ->and($workspace->expires_at->isBetween(now()->addHours(23), now()->addHours(25)))->toBeTrue()
        ->and($admin->role)->toBe(Role::Admin)
        ->and($client->role)->toBe(Role::Client)
        ->and($admin->email)->toEndWith('@sandbox.codelaunch.nl')
        ->and(Hash::check($credentials['admin']['password'], $admin->password))->toBeTrue()
        ->and(Organization::count())->toBe(3)
        ->and(Project::count())->toBe(6)
        ->and(ActivityEntry::where('visible_to_client', true)->exists())->toBeTrue();

    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->where('demo.admin.email', $credentials['admin']['email'])
        ->where('demo.client.email', $credentials['client']['email']));
});

test('the visitor really logs in with the generated credentials', function () {
    $credentials = sandboxCredentials();

    $this->post(route('login.store'), $credentials['admin'])->assertRedirect(route('admin.dashboard'));
    $this->post(route('logout'));
    $this->post(route('login.store'), $credentials['client'])->assertRedirect(route('client.home'));
});

test('the demo client sees their organization\'s projects with a history', function () {
    $credentials = sandboxCredentials();
    $this->post(route('login.store'), $credentials['client']);

    $this->get(route('client.home'))->assertInertia(fn (Assert $page) => $page->has('projects', 3));
});

test('the demo client\'s launch checks codelaunch.nl and is part-way through', function () {
    $credentials = sandboxCredentials();
    $this->post(route('login.store'), $credentials['client']);

    $project = Project::where('name', 'Online pre-orders')->sole();
    $launch = $project->launch;

    expect($project->phase)->toBe(ProjectPhase::Launch)
        ->and($launch->url)->toBe('https://codelaunch.nl')
        ->and($launch->checklistItems->whereNotNull('checked_at')->pluck('label')->all())
        ->toBe(['404 page', 'Favicon', 'Forms tested']);

    $this->get(route('client.home'))->assertInertia(fn (Assert $page) => $page
        ->where('waiting.0.title', 'Finish your launch checklist (2 items left)')
        ->where('waiting.0.module', 'Launch Control'));

    $this->get(route('client.launch.show', $project))->assertOk();
});

test('two sandboxes never see each other\'s data', function () {
    $first = app(SandboxFactory::class)->create();
    $second = app(SandboxFactory::class)->create();

    $this->actingAs($first->admin);

    expect(Organization::count())->toBe(3)
        ->and(Project::pluck('workspace_id')->unique()->all())->toBe([$first->workspace->id]);

    $theirs = Project::withoutGlobalScopes()->where('workspace_id', $second->workspace->id)->first();
    $this->get(route('admin.projects.show', $theirs))->assertNotFound();
});

test('signed-in users cannot start a demo', function () {
    $this->actingAs(User::factory()->create())->post(route('demo.store'))->assertRedirect();

    expect(Workspace::where('is_sandbox', true)->count())->toBe(0);
});

test('a visitor can start at most five demos an hour', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('demo.store'))->assertRedirect(route('login'));
        $this->flushSession();
    }

    $this->post(route('demo.store'))->assertTooManyRequests();
    expect(Workspace::count())->toBe(5);
});

test('the demo says it is busy at the active-sandbox cap', function () {
    config(['demo.max_active' => 1]);
    app(SandboxFactory::class)->create();

    $this->post(route('demo.store'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'busy'));

    expect(Workspace::count())->toBe(1);
});

test('the demo can be switched off', function () {
    config(['demo.enabled' => false]);

    $this->post(route('demo.store'))->assertNotFound();
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('demoEnabled', false));
});

test('sandbox users can switch between studio and client', function () {
    $sandbox = app(SandboxFactory::class)->create();

    $this->actingAs($sandbox->admin)
        ->post(route('demo.switch'))
        ->assertRedirect(route('client.home'));
    $this->assertAuthenticatedAs($sandbox->client);

    $this->post(route('demo.switch'))->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($sandbox->admin);
});

test('switching stays inside the sandbox, even with extra contacts', function () {
    $sandbox = app(SandboxFactory::class)->create();
    app(SandboxFactory::class)->create();

    $extra = User::factory()->client(Organization::withoutGlobalScopes()
        ->where('workspace_id', $sandbox->workspace->id)->first())->create();

    $this->actingAs($sandbox->admin)->post(route('demo.switch'));

    $this->assertAuthenticatedAs($sandbox->client);
    expect($extra->id)->not->toBe($sandbox->client->id);
});

test('real users cannot switch identity', function () {
    $organization = Organization::factory()->create();
    User::factory()->client($organization)->create();

    $this->actingAs(User::factory()->admin()->inWorkspace($organization->workspace)->create())
        ->post(route('demo.switch'))
        ->assertForbidden();
});

test('sandbox users cannot change their login, add 2FA or delete their account', function () {
    $sandbox = app(SandboxFactory::class)->create();
    $this->actingAs($sandbox->admin);

    $this->put(route('user-password.update'), [
        'current_password' => $sandbox->adminPassword,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertForbidden();

    $this->patch(route('profile.update'), ['name' => 'Me', 'email' => 'mine@example.test'])->assertForbidden();
    $this->post(route('two-factor.enable'))->assertForbidden();
    $this->delete(route('profile.destroy'), ['password' => $sandbox->adminPassword])->assertForbidden();

    expect($sandbox->admin->fresh()->email)->toBe($sandbox->admin->email)
        ->and($sandbox->admin->fresh()->two_factor_secret)->toBeNull();
});

test('sandbox users can still change their display name', function () {
    $sandbox = app(SandboxFactory::class)->create();

    $this->actingAs($sandbox->admin)
        ->patch(route('profile.update'), ['name' => 'Visitor', 'email' => $sandbox->admin->email])
        ->assertSessionHasNoErrors();

    expect($sandbox->admin->fresh()->name)->toBe('Visitor');
});

test('real users keep every account setting', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasNoErrors();
});

test('an expired sandbox logs its user out', function () {
    $sandbox = app(SandboxFactory::class)->create();
    $sandbox->workspace->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->actingAs($sandbox->admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('sandbox:prune deletes only expired sandboxes, with everything in them', function () {
    $expired = app(SandboxFactory::class)->create();
    $expired->workspace->forceFill(['expires_at' => now()->subMinute()])->save();
    $active = app(SandboxFactory::class)->create();
    $real = Workspace::factory()->create(['expires_at' => now()->subYear()]);

    $this->artisan('sandbox:prune')->expectsOutputToContain('Deleted 1')->assertSuccessful();

    expect(Workspace::pluck('id')->sort()->values()->all())->toBe(collect([$active->workspace->id, $real->id])->sort()->values()->all())
        ->and(User::withoutGlobalScopes()->where('workspace_id', $expired->workspace->id)->exists())->toBeFalse()
        ->and(Project::withoutGlobalScopes()->where('workspace_id', $expired->workspace->id)->exists())->toBeFalse()
        ->and(ActivityEntry::withoutGlobalScopes()->where('workspace_id', $expired->workspace->id)->exists())->toBeFalse();
});

test('sandbox:prune is scheduled hourly', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command ?? '', 'sandbox:prune'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *');
});

test('the banner props are shared with sandbox users only', function () {
    $sandbox = app(SandboxFactory::class)->create();

    $this->actingAs($sandbox->admin)->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspace.isSandbox', true)
            ->has('workspace.expiresAt'));

    $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('workspace.isSandbox', false));
});
