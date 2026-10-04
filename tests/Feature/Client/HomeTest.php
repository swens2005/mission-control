<?php

use App\Enums\ProjectPhase;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Waiting\WaitingItem;
use App\Support\Waiting\WaitingOnClient;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'Bakkerij de Vries']);
    $this->client = User::factory()->client($this->organization)->create();
    $this->actingAs($this->client);
});

test('lists only the client\'s own active projects, soonest launch first', function () {
    Project::factory()->for($this->organization)->create(['name' => 'Later', 'target_launch_on' => '2027-02-01']);
    Project::factory()->for($this->organization)->phase(ProjectPhase::Palette)->create(['name' => 'Sooner', 'target_launch_on' => '2026-11-01']);
    Project::factory()->for($this->organization)->archived()->create(['name' => 'Archived']);

    $neighbour = Organization::factory()->for($this->organization->workspace)->create();
    Project::factory()->for($neighbour)->create(['name' => 'Same studio, other client']);
    Project::factory()->create(['name' => 'Other studio']);

    $this->get(route('client.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('client/home')
            ->where('organizationName', 'Bakkerij de Vries')
            ->has('projects', 2)
            ->where('projects.0.name', 'Sooner')
            ->where('projects.0.step', 2)
            ->where('projects.0.phaseLabel', 'Palette Lab')
            ->where('projects.1.name', 'Later')
            ->has('steps', 4)
            ->where('waiting', []));
});

test('shows one of the client\'s projects', function () {
    $project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create();

    $this->get(route('client.projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('client/projects/show')
            ->where('project.id', $project->id)
            ->where('project.step', 3)
            ->missing('project.organization'));
});

test('another organization\'s project is a 404, even in the same studio', function () {
    $neighbour = Organization::factory()->for($this->organization->workspace)->create();
    $project = Project::factory()->for($neighbour)->create();

    $this->get(route('client.projects.show', $project))->assertNotFound();
});

test('another studio\'s project is a 404', function () {
    $this->get(route('client.projects.show', Project::factory()->create()))->assertNotFound();
});

test('an archived project is a 404', function () {
    $project = Project::factory()->for($this->organization)->archived()->create();

    $this->get(route('client.projects.show', $project))->assertNotFound();
});

test('items waiting on the client are listed', function () {
    $project = Project::factory()->for($this->organization)->create(['name' => 'New site']);

    app(WaitingOnClient::class)->register(fn (User $client) => [
        new WaitingItem('Approve design round 2', 'Proofmark', $project->id, $project->name, '/client/projects/'.$project->id, CarbonImmutable::parse('2026-11-01')),
    ]);

    $this->get(route('client.home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('waiting', 1)
            ->where('waiting.0.title', 'Approve design round 2')
            ->where('waiting.0.dueOn', '2026-11-01'));
});
