<?php

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->ours = Workspace::factory()->create();
    $this->theirs = Workspace::factory()->create();

    $this->ourOrganization = Organization::factory()->for($this->ours)->create(['name' => 'Ours']);
    $this->theirOrganization = Organization::factory()->for($this->theirs)->create(['name' => 'Theirs']);

    $this->ourProject = Project::factory()->for($this->ourOrganization)->create();
    $this->theirProject = Project::factory()->for($this->theirOrganization)->create();

    $this->admin = User::factory()->inWorkspace($this->ours)->create();
});

test('a signed-in user only sees their own workspace', function () {
    Auth::login($this->admin);

    expect(Organization::pluck('name')->all())->toBe(['Ours'])
        ->and(Project::pluck('id')->all())->toBe([$this->ourProject->id])
        ->and(User::pluck('id')->all())->toBe([$this->admin->id]);
});

test('records from another workspace cannot be found by id', function () {
    Auth::login($this->admin);

    expect(Organization::find($this->theirOrganization->id))->toBeNull()
        ->and(Project::find($this->theirProject->id))->toBeNull();
});

test('without a signed-in user there is no implicit scope', function () {
    expect(Organization::count())->toBe(2);
});

test('workspace_id is filled in from the signed-in user', function () {
    Auth::login($this->admin);

    $organization = Organization::create(['name' => 'New client']);

    expect($organization->workspace_id)->toBe($this->ours->id);
});

test('workspace_id cannot be mass assigned', function () {
    Auth::login($this->admin);

    $organization = Organization::create(['name' => 'Sneaky', 'workspace_id' => $this->theirs->id]);

    expect($organization->workspace_id)->toBe($this->ours->id);
});

test('a project inherits its organization\'s workspace outside a request', function () {
    $project = Project::create(['organization_id' => $this->theirOrganization->id, 'name' => 'Seeded']);

    expect($project->workspace_id)->toBe($this->theirs->id);
});

test('a project cannot link to an organization in another workspace', function () {
    Auth::login($this->admin);

    Project::create(['organization_id' => $this->theirOrganization->id, 'name' => 'Cross-link']);
})->throws(LogicException::class);

test('a client must belong to an organization', function () {
    User::factory()->create(['role' => Role::Client, 'organization_id' => null]);
})->throws(LogicException::class, 'A client must belong to an organization.');

test('a client\'s organization must be in the client\'s workspace', function () {
    User::factory()->inWorkspace($this->ours)->create([
        'role' => Role::Client,
        'organization_id' => $this->theirOrganization->id,
    ]);
})->throws(LogicException::class);

test('an admin cannot belong to an organization', function () {
    User::factory()->inWorkspace($this->ours)->create([
        'role' => Role::Admin,
        'organization_id' => $this->ourOrganization->id,
    ]);
})->throws(LogicException::class);

test('new users are clients unless created as admins', function () {
    expect((new User)->role)->toBe(Role::Client);
});

test('deleting a workspace removes everything in it', function () {
    $client = User::factory()->client($this->ourOrganization)->create();

    $this->ours->delete();

    expect(Organization::find($this->ourOrganization->id))->toBeNull()
        ->and(Project::find($this->ourProject->id))->toBeNull()
        ->and(User::find($this->admin->id))->toBeNull()
        ->and(User::find($client->id))->toBeNull()
        ->and(Organization::find($this->theirOrganization->id))->not->toBeNull();
});
