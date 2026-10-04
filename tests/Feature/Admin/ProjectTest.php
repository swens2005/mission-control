<?php

use App\Enums\ProjectPhase;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create(['name' => 'Acme']);
    $this->actingAs($this->admin);
});

function validProject(array $overrides = []): array
{
    return [
        'organization_id' => test()->organization->id,
        'name' => 'New website',
        'description' => 'A fresh start.',
        'phase' => 'scope',
        'target_launch_on' => '2026-12-01',
        ...$overrides,
    ];
}

test('lists active projects, soonest launch first', function () {
    Project::factory()->for($this->organization)->create(['name' => 'Later', 'target_launch_on' => '2027-03-01']);
    Project::factory()->for($this->organization)->create(['name' => 'Sooner', 'target_launch_on' => '2026-11-01']);
    Project::factory()->for($this->organization)->archived()->create(['name' => 'Archived']);
    Project::factory()->create(['name' => 'Other workspace']);

    $this->get(route('admin.projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/projects/index')
            ->has('projects.data', 2)
            ->where('projects.data.0.name', 'Sooner')
            ->where('projects.data.0.organization.name', 'Acme')
            ->has('organizations', 1));
});

test('filters projects by client and phase', function () {
    $other = Organization::factory()->for($this->admin->workspace)->create();
    Project::factory()->for($this->organization)->phase(ProjectPhase::Launch)->create();
    Project::factory()->for($this->organization)->phase(ProjectPhase::Scope)->create();
    Project::factory()->for($other)->phase(ProjectPhase::Launch)->create();

    $this->get(route('admin.projects.index', ['organization' => $this->organization->id]))
        ->assertInertia(fn (Assert $page) => $page->has('projects.data', 2));

    $this->get(route('admin.projects.index', ['organization' => $this->organization->id, 'phase' => 'launch']))
        ->assertInertia(fn (Assert $page) => $page->has('projects.data', 1));
});

test('rejects an unknown phase filter', function () {
    $this->get(route('admin.projects.index', ['phase' => 'nonsense']))->assertSessionHasErrors('phase');
});

test('creates a project', function () {
    $this->get(route('admin.projects.create', ['organization' => $this->organization->id]))
        ->assertInertia(fn (Assert $page) => $page->where('organizationId', $this->organization->id));

    $this->post(route('admin.projects.store'), validProject())->assertRedirect();

    $project = Project::sole();

    expect($project->name)->toBe('New website')
        ->and($project->phase)->toBe(ProjectPhase::Scope)
        ->and($project->target_launch_on->toDateString())->toBe('2026-12-01')
        ->and($project->workspace_id)->toBe($this->admin->workspace_id);
});

test('validates the project form', function (array $overrides, string $field) {
    $this->post(route('admin.projects.store'), validProject($overrides))->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'unknown phase' => [['phase' => 'rocket'], 'phase'],
    'bad date' => [['target_launch_on' => 'soon'], 'target_launch_on'],
    'missing client' => [['organization_id' => null], 'organization_id'],
]);

test('cannot create a project for another workspace\'s client', function () {
    $theirs = Organization::factory()->create();

    $this->post(route('admin.projects.store'), validProject(['organization_id' => $theirs->id]))
        ->assertSessionHasErrors('organization_id');

    expect(Project::count())->toBe(0);
});

test('cannot create a project for an archived client', function () {
    $this->organization->forceFill(['archived_at' => now()])->save();

    $this->post(route('admin.projects.store'), validProject())->assertSessionHasErrors('organization_id');
});

test('shows, edits and updates a project', function () {
    $project = Project::factory()->for($this->organization)->create();

    $this->get(route('admin.projects.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/projects/show')
            ->where('project.id', $project->id)
            ->where('project.phaseLabel', 'Scope'));

    $this->get(route('admin.projects.edit', $project))->assertOk();

    $this->put(route('admin.projects.update', $project), validProject(['phase' => 'proofmark', 'name' => 'Renamed']))
        ->assertRedirect(route('admin.projects.show', $project));

    expect($project->fresh())
        ->name->toBe('Renamed')
        ->phase->toBe(ProjectPhase::Proofmark);
});

test('archives and restores a project', function () {
    $project = Project::factory()->for($this->organization)->create();

    $this->post(route('admin.projects.archive', $project));
    expect($project->fresh()->isArchived())->toBeTrue();

    $this->delete(route('admin.projects.unarchive', $project));
    expect($project->fresh()->isArchived())->toBeFalse();
});

test('an admin from another workspace gets 404 on every project route', function () {
    $theirs = Project::factory()->create(['name' => 'Theirs']);

    $this->get(route('admin.projects.show', $theirs))->assertNotFound();
    $this->get(route('admin.projects.edit', $theirs))->assertNotFound();
    $this->put(route('admin.projects.update', $theirs), validProject())->assertNotFound();
    $this->post(route('admin.projects.archive', $theirs))->assertNotFound();
    $this->delete(route('admin.projects.unarchive', $theirs))->assertNotFound();

    expect($theirs->fresh()->name)->toBe('Theirs');
});

test('clients cannot reach any project-management route', function () {
    $project = Project::factory()->for($this->organization)->create();
    $this->actingAs(User::factory()->client($this->organization)->create());

    $this->get(route('admin.projects.index'))->assertForbidden();
    $this->get(route('admin.projects.show', $project))->assertForbidden();
    $this->post(route('admin.projects.store'), validProject())->assertForbidden();
});

test('launched projects are listed after the ones still in flight', function () {
    Project::factory()->for($this->organization)->phase(ProjectPhase::Launched)->create(['name' => 'Done', 'target_launch_on' => '2026-01-01']);
    Project::factory()->for($this->organization)->create(['name' => 'In flight', 'target_launch_on' => '2026-12-01']);

    $this->get(route('admin.projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.data.0.name', 'In flight')
            ->where('projects.data.1.name', 'Done'));
});
