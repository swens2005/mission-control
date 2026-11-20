<?php

use App\Enums\ProjectPhase;
use App\Models\Intake;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Activity;
use App\Support\FlightDeck;
use App\Support\Waiting\WaitingItem;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-11-20 10:00:00');
    $this->admin = User::factory()->admin()->create(['name' => 'Sam Visser']);
    $this->organization = Organization::factory()->for($this->admin->workspace)->create(['name' => 'Acme']);
    $this->actingAs($this->admin);
});

test('shows the missions in flight, soonest launch first', function () {
    Project::factory()->for($this->organization)->create(['name' => 'Later', 'target_launch_on' => '2027-03-01']);
    Project::factory()->for($this->organization)->create(['name' => 'No date', 'target_launch_on' => null]);
    Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create(['name' => 'Sooner', 'target_launch_on' => '2026-12-01']);
    Project::factory()->for($this->organization)->phase(ProjectPhase::Launched)->create(['name' => 'Launched']);
    Project::factory()->for($this->organization)->archived()->create(['name' => 'Archived']);
    Project::factory()->create(['name' => 'Other studio', 'target_launch_on' => '2026-11-25']);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('firstName', 'Sam')
            ->where('readouts.inFlight', 3)
            ->where('readouts.nextLaunch', ['on' => '2026-12-01', 'project' => 'Sooner'])
            ->has('missions', 3)
            ->where('missions.0.name', 'Sooner')
            ->where('missions.0.client', 'Acme')
            ->where('missions.0.phaseLabel', 'Proofmark')
            ->where('missions.0.step', 3)
            ->where('missions.1.name', 'Later')
            ->where('missions.2.name', 'No date')
            ->where('missions.0.urls.proofmark', route('admin.proofmark.show', Project::query()->where('name', 'Sooner')->first())));
});

test('lists what the clients owe the studio, linking to the admin project', function () {
    $project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create(['name' => 'Bakery', 'target_launch_on' => '2026-12-01']);
    ReviewRound::factory()->for($project)->inReview()->create();

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('readouts.waiting', 1)
            ->has('waiting', 1)
            ->where('waiting.0.module', 'Proofmark')
            ->where('waiting.0.client', 'Acme')
            ->where('waiting.0.projectName', 'Bakery')
            ->where('waiting.0.dueOn', '2026-12-01')
            ->where('waiting.0.url', route('admin.projects.show', $project))
            ->where('missions.0.waiting', ['Review design round v1']));
});

test('a whole organization owes exactly what its clients see', function () {
    $project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create();
    ReviewRound::factory()->for($project)->inReview()->create();
    Intake::factory()->for($project)->create();
    $client = User::factory()->client($this->organization)->create();

    $titles = fn (array $items) => array_map(fn (WaitingItem $item) => $item->title, $items);
    $waiting = app(WaitingOnClient::class);

    expect($titles($waiting->forOrganization($this->organization)))
        ->toBe($titles($waiting->for($client)))
        ->not->toBeEmpty();
});

test('adds up the proposals accepted this calendar month', function () {
    $project = Project::factory()->for($this->organization)->create();
    $accepted = fn (string $at, int $total, int $number) => Proposal::factory()->for($project)->create([
        'number' => $number,
        'sent_at' => '2026-10-01',
        'accepted_at' => $at,
        'total_cents' => $total,
    ]);
    $accepted('2026-11-03 09:00', 425000, 1);
    $accepted('2026-11-19 16:00', 100050, 2);
    $accepted('2026-10-31 23:00', 999900, 3);
    Proposal::factory()->for($project)->create(['number' => 4, 'sent_at' => '2026-11-10', 'total_cents' => 5000]);

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('readouts.acceptedThisMonth.amount', '€5,250.50')
            ->where('readouts.acceptedThisMonth.count', 2));
});

test('suggests Navigator when the intake is in and the draft is still empty', function () {
    config(['services.anthropic.key' => 'test-key']);

    $empty = Project::factory()->for($this->organization)->create(['name' => 'Empty draft', 'target_launch_on' => '2026-12-01']);
    Intake::factory()->for($empty)->submitted()->create();
    Proposal::factory()->for($empty)->create();

    $started = Project::factory()->for($this->organization)->create(['name' => 'Started draft', 'target_launch_on' => '2026-12-02']);
    Intake::factory()->for($started)->submitted()->create();
    ProposalLine::factory()->for(Proposal::factory()->for($started)->create())->create();

    $noIntake = Project::factory()->for($this->organization)->create(['name' => 'No intake', 'target_launch_on' => '2026-12-03']);
    Proposal::factory()->for($noIntake)->create();

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('missions.0.navigator', true)
            ->where('missions.1.navigator', false)
            ->where('missions.2.navigator', false));

    config(['services.anthropic.key' => null]);

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('missions.0.navigator', false));
});

test('the mission log shows the newest entries only', function () {
    $project = Project::factory()->for($this->organization)->create();

    foreach (range(1, FlightDeck::LOG_SIZE + 2) as $i) {
        $this->travel(1)->minutes();
        Activity::record('project.updated', $project, ['name' => "Update {$i}"], actor: $this->admin);
    }

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('log', FlightDeck::LOG_SIZE)
            ->where('log.0.description', 'updated project Update '.(FlightDeck::LOG_SIZE + 2))
            ->where('log.0.actor', 'Sam Visser'));
});

test('an empty studio gets an empty flight deck', function () {
    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('readouts.inFlight', 0)
            ->where('readouts.waiting', 0)
            ->where('readouts.nextLaunch', null)
            ->where('readouts.acceptedThisMonth', ['amount' => '€0.00', 'count' => 0])
            ->has('missions', 0)
            ->has('waiting', 0));
});

test('clients cannot open the flight deck', function () {
    $this->actingAs(User::factory()->client($this->organization)->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
