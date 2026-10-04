<?php

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

function ourOrganization(array $attributes = []): Organization
{
    return Organization::factory()->for(test()->admin->workspace)->create($attributes);
}

test('lists active clients, alphabetically, with counts', function () {
    $acme = ourOrganization(['name' => 'Acme']);
    ourOrganization(['name' => 'Bakery']);
    ourOrganization(['name' => 'Archived Co'])->forceFill(['archived_at' => now()])->save();
    Project::factory()->for($acme)->count(2)->create();
    User::factory()->client($acme)->create();
    Organization::factory()->create(['name' => 'Another workspace']);

    $this->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/organizations/index')
            ->has('organizations.data', 2)
            ->where('organizations.data.0.name', 'Acme')
            ->where('organizations.data.0.projectsCount', 2)
            ->where('organizations.data.0.contactsCount', 1)
            ->where('organizations.data.1.name', 'Bakery'));
});

test('searches by name and can include archived clients', function () {
    ourOrganization(['name' => 'Acme']);
    ourOrganization(['name' => 'Acme Archive'])->forceFill(['archived_at' => now()])->save();
    ourOrganization(['name' => 'Bakery']);

    $this->get(route('admin.organizations.index', ['search' => 'acme']))
        ->assertInertia(fn (Assert $page) => $page->has('organizations.data', 1));

    $this->get(route('admin.organizations.index', ['search' => 'acme', 'archived' => 1]))
        ->assertInertia(fn (Assert $page) => $page->has('organizations.data', 2));
});

test('search treats % and _ literally', function () {
    ourOrganization(['name' => 'Acme']);

    $this->get(route('admin.organizations.index', ['search' => '%']))
        ->assertInertia(fn (Assert $page) => $page->has('organizations.data', 0));
});

test('creates a client in the admin\'s workspace', function () {
    $this->post(route('admin.organizations.store'), [
        'name' => 'Bakkerij de Vries',
        'website_url' => 'https://example.com',
    ])->assertRedirect();

    $organization = Organization::sole();

    expect($organization->name)->toBe('Bakkerij de Vries')
        ->and($organization->workspace_id)->toBe($this->admin->workspace_id);
});

test('validates the client form', function (array $data, string $field) {
    $this->post(route('admin.organizations.store'), $data)->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'not a URL' => [['name' => 'Acme', 'website_url' => 'not a url'], 'website_url'],
    'javascript: URL' => [['name' => 'Acme', 'website_url' => 'javascript:alert(1)'], 'website_url'],
]);

test('shows a client with its projects and contacts', function () {
    $organization = ourOrganization(['name' => 'Acme']);
    Project::factory()->for($organization)->create(['name' => 'New site']);
    User::factory()->client($organization)->create(['name' => 'Anna']);

    $this->get(route('admin.organizations.show', $organization))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/organizations/show')
            ->where('organization.name', 'Acme')
            ->where('projects.0.name', 'New site')
            ->where('contacts.0.name', 'Anna')
            ->missing('organization.workspace_id'));
});

test('updates a client', function () {
    $organization = ourOrganization();

    $this->get(route('admin.organizations.edit', $organization))->assertOk();
    $this->put(route('admin.organizations.update', $organization), ['name' => 'Renamed'])
        ->assertRedirect(route('admin.organizations.show', $organization));

    expect($organization->fresh()->name)->toBe('Renamed');
});

test('archives and restores a client', function () {
    $organization = ourOrganization();

    $this->post(route('admin.organizations.archive', $organization));
    expect($organization->fresh()->isArchived())->toBeTrue();

    $this->delete(route('admin.organizations.unarchive', $organization));
    expect($organization->fresh()->isArchived())->toBeFalse();
});

test('adds a contact and shows the temporary password once', function () {
    $organization = ourOrganization();

    $password = null;

    $this->followingRedirects()
        ->post(route('admin.organizations.contacts.store', $organization), [
            'name' => 'Anna de Vries',
            'email' => 'anna@example.test',
        ])
        ->assertInertia(function (Assert $page) use (&$password) {
            $page->component('admin/organizations/show')
                ->hasFlash('newContact.email', 'anna@example.test');

            $password = $page->toArray()['flash']['newContact']['temporaryPassword'];
        });

    $contact = User::where('email', 'anna@example.test')->sole();

    expect($contact->role)->toBe(Role::Client)
        ->and($contact->organization_id)->toBe($organization->id)
        ->and($contact->workspace_id)->toBe($organization->workspace_id)
        ->and(strlen($password))->toBe(16)
        ->and($contact->password)->not->toBe($password)
        ->and(Hash::check($password, $contact->password))->toBeTrue();

    // The next page load no longer carries it.
    $this->get(route('admin.organizations.show', $organization))
        ->assertInertia(fn (Assert $page) => $page->missingFlash('newContact'));
});

test('contact emails must be unique across all workspaces', function () {
    $organization = ourOrganization();
    User::factory()->create(['email' => 'taken@example.test']);

    $this->post(route('admin.organizations.contacts.store', $organization), [
        'name' => 'Someone',
        'email' => 'taken@example.test',
    ])->assertSessionHasErrors('email');
});

test('archived clients cannot get new contacts', function () {
    $organization = ourOrganization(['archived_at' => now()]);

    $this->post(route('admin.organizations.contacts.store', $organization), [
        'name' => 'Someone',
        'email' => 'someone@example.test',
    ])->assertForbidden();
});

test('an admin from another workspace gets 404 on every client route', function () {
    $theirs = Organization::factory()->create();

    $this->get(route('admin.organizations.show', $theirs))->assertNotFound();
    $this->get(route('admin.organizations.edit', $theirs))->assertNotFound();
    $this->put(route('admin.organizations.update', $theirs), ['name' => 'Hijacked'])->assertNotFound();
    $this->post(route('admin.organizations.archive', $theirs))->assertNotFound();
    $this->delete(route('admin.organizations.unarchive', $theirs))->assertNotFound();
    $this->post(route('admin.organizations.contacts.store', $theirs), [
        'name' => 'Intruder',
        'email' => 'intruder@example.test',
    ])->assertNotFound();

    expect($theirs->fresh()->name)->not->toBe('Hijacked')
        ->and(User::where('email', 'intruder@example.test')->exists())->toBeFalse();
});

test('clients cannot reach any client-management route', function () {
    $organization = ourOrganization();
    $client = User::factory()->client($organization)->create();

    $this->actingAs($client);

    $this->get(route('admin.organizations.index'))->assertForbidden();
    $this->post(route('admin.organizations.store'), ['name' => 'X'])->assertForbidden();
    $this->post(route('admin.organizations.contacts.store', $organization), [
        'name' => 'Friend',
        'email' => 'friend@example.test',
    ])->assertForbidden();
});
