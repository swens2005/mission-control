<?php

use App\Models\ActivityEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Meagan']);
    $this->organization = Organization::factory()->for($this->admin->workspace)->create(['name' => 'Acme']);
    $this->actingAs($this->admin);
});

function events(): array
{
    return ActivityEntry::orderBy('id')->pluck('event')->all();
}

test('client changes are recorded with the actor', function () {
    $this->post(route('admin.organizations.store'), ['name' => 'Bakery']);
    $bakery = Organization::where('name', 'Bakery')->sole();
    $this->put(route('admin.organizations.update', $bakery), ['name' => 'Bakery BV']);
    $this->post(route('admin.organizations.archive', $bakery));
    $this->delete(route('admin.organizations.unarchive', $bakery));
    $this->post(route('admin.organizations.contacts.store', $bakery), ['name' => 'Anna', 'email' => 'anna@example.test']);

    expect(events())->toBe([
        'organization.created',
        'organization.updated',
        'organization.archived',
        'organization.restored',
        'contact.added',
    ]);

    $entry = ActivityEntry::first();

    expect($entry->actor_id)->toBe($this->admin->id)
        ->and($entry->workspace_id)->toBe($this->admin->workspace_id)
        ->and($entry->description())->toBe('added client Bakery')
        ->and(ActivityEntry::latest('id')->first()->description())->toBe('added Anna as a contact for Bakery BV');
});

test('saving a client without changes records nothing', function () {
    $this->put(route('admin.organizations.update', $this->organization), [
        'name' => $this->organization->name,
        'website_url' => $this->organization->website_url,
    ]);

    expect(events())->toBe([]);
});

test('project changes are recorded, and phase moves are shown to the client', function () {
    $this->post(route('admin.projects.store'), [
        'organization_id' => $this->organization->id,
        'name' => 'New site',
        'phase' => 'scope',
    ]);
    $project = Project::sole();

    $this->put(route('admin.projects.update', $project), [
        'organization_id' => $this->organization->id,
        'name' => 'New site',
        'phase' => 'palette',
    ]);
    $this->put(route('admin.projects.update', $project), [
        'organization_id' => $this->organization->id,
        'name' => 'Renamed site',
        'phase' => 'palette',
    ]);
    $this->post(route('admin.projects.archive', $project));

    expect(events())->toBe(['project.created', 'project.phase_changed', 'project.updated', 'project.archived']);

    $phase = ActivityEntry::where('event', 'project.phase_changed')->sole();

    expect($phase->project_id)->toBe($project->id)
        ->and($phase->visible_to_client)->toBeTrue()
        ->and($phase->description())->toBe('moved New site from Scope to Palette Lab')
        ->and(ActivityEntry::where('event', 'project.updated')->sole()->visible_to_client)->toBeFalse();
});

test('entries cannot be updated', function () {
    $entry = Activity::record('organization.created', $this->organization, ['name' => 'Acme']);

    $entry->forceFill(['event' => 'tampered'])->save();
})->throws(LogicException::class, 'cannot be changed');

test('entries cannot be deleted', function () {
    Activity::record('organization.created', $this->organization, ['name' => 'Acme'])->delete();
})->throws(LogicException::class, 'cannot be deleted');

test('entries disappear with their workspace', function () {
    Activity::record('organization.created', $this->organization, ['name' => 'Acme']);

    $this->admin->workspace->delete();

    expect(ActivityEntry::withoutGlobalScopes()->count())->toBe(0);
});

test('entries survive the actor deleting their account', function () {
    $entry = Activity::record('organization.created', $this->organization, ['name' => 'Acme']);
    $other = User::factory()->admin()->inWorkspace($this->admin->workspace)->create();

    $entry2 = Activity::record('organization.updated', $this->organization, ['name' => 'Acme'], actor: $other);
    $other->delete();

    expect($entry2->fresh()->actor_id)->toBeNull()
        ->and($entry->fresh())->not->toBeNull();
});

test('the admin activity page shows the workspace, newest first, filterable by project', function () {
    $project = Project::factory()->for($this->organization)->create(['name' => 'Site']);
    Activity::record('organization.created', $this->organization, ['name' => 'Acme']);
    Activity::record('project.created', $project, ['name' => 'Site']);

    $theirs = Organization::factory()->create();
    Activity::record('organization.created', $theirs, ['name' => 'Theirs'], actor: User::factory()->create());

    $this->get(route('admin.activity.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/activity/index')
            ->has('entries.data', 2)
            ->where('entries.data.0.description', 'started project Site')
            ->where('entries.data.0.actor', 'Meagan')
            ->where('entries.data.1.description', 'added client Acme'));

    $this->get(route('admin.activity.index', ['project' => $project->id]))
        ->assertInertia(fn (Assert $page) => $page->has('entries.data', 1));
});

test('the admin project page shows that project\'s activity', function () {
    $project = Project::factory()->for($this->organization)->create();
    $other = Project::factory()->for($this->organization)->create();
    Activity::record('project.created', $project, ['name' => $project->name]);
    Activity::record('project.created', $other, ['name' => $other->name]);

    $this->get(route('admin.projects.show', $project))
        ->assertInertia(fn (Assert $page) => $page->has('activity', 1));
});

test('clients only see entries marked for them, on their own project', function () {
    $project = Project::factory()->for($this->organization)->create(['name' => 'Site']);
    Activity::record('project.created', $project, ['name' => 'Site'], visibleToClient: true);
    Activity::record('project.updated', $project, ['name' => 'Site']);

    $client = User::factory()->client($this->organization)->create();

    $this->actingAs($client)
        ->get(route('client.projects.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('activity', 1)
            ->where('activity.0.description', 'started project Site'));
});

test('clients cannot open the admin activity page', function () {
    $this->actingAs(User::factory()->client($this->organization)->create())
        ->get(route('admin.activity.index'))
        ->assertForbidden();
});
