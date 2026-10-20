<?php

use App\Enums\IntakeBudget;
use App\Models\ActivityEntry;
use App\Models\Intake;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->create(['name' => 'Seasonal menu pages']);
    $this->client = User::factory()->client($this->organization)->create(['name' => 'Anna de Vries']);
});

function intakeWaitingTitles(User $client): array
{
    return array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($client));
}

/**
 * @return array<string, mixed>
 */
function intakeAnswers(array $overrides = []): array
{
    return [
        'goals' => 'Customers should see the Christmas specials and order ahead.',
        'pages' => ['Christmas', '', '  Easter  '],
        'features' => ['contact_form', 'shop'],
        'features_other' => 'Allergy info per product',
        'budget' => '2500_5000',
        'deadline_on' => now()->addMonth()->toDateString(),
        'deadline_reason' => 'Before Christmas',
        ...$overrides,
    ];
}

function askIntake(object $test): Intake
{
    $test->actingAs($test->admin)
        ->post(route('admin.intakes.store', $test->project))
        ->assertRedirect(route('admin.scope.show', $test->project));

    return $test->project->intake()->sole();
}

test('the studio asks for the intake, which the client then sees', function () {
    $this->actingAs($this->client)->get(route('client.scope.show', $this->project))->assertNotFound();

    $this->actingAs($this->admin)
        ->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('admin/scope/show')->where('intake', null));

    $intake = askIntake($this);

    // Asking twice keeps the one intake.
    $this->post(route('admin.intakes.store', $this->project))->assertRedirect();
    expect(Intake::count())->toBe(1)
        ->and(ActivityEntry::where('event', 'scope.intake_requested')->sole()->visible_to_client)->toBeTrue();

    $this->actingAs($this->client)
        ->get(route('client.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('client/scope/show')
            ->where('intake.id', $intake->id)
            ->where('intake.submitted', false)
            ->has('options.features', 7)
            ->has('options.budgets', 6));

    $this->get(route('client.projects.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('hasScope', true));
});

test('the client saves a draft, then sends it', function () {
    $intake = askIntake($this);

    expect(intakeWaitingTitles($this->client))->toContain('Fill in the intake');

    // A draft may be incomplete.
    $this->actingAs($this->client)
        ->put(route('client.intakes.update', $intake), ['goals' => 'More orders'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('client.scope.show', $this->project));

    expect($intake->refresh()->goals)->toBe('More orders')
        ->and($intake->isSubmitted())->toBeFalse();

    $this->put(route('client.intakes.update', $intake), [...intakeAnswers(), 'submit' => true], ['REMOTE_ADDR' => '203.0.113.7'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('client.scope.show', $this->project));

    $intake->refresh();

    expect($intake->isSubmitted())->toBeTrue()
        ->and($intake->submitted_by_name)->toBe('Anna de Vries')
        ->and($intake->pages)->toBe(['Christmas', 'Easter'])
        ->and($intake->features)->toBe(['contact_form', 'shop'])
        ->and($intake->budget)->toBe(IntakeBudget::From2500)
        ->and($intake->deadline_reason)->toBe('Before Christmas')
        ->and(intakeWaitingTitles($this->client))->not->toContain('Fill in the intake')
        ->and(ActivityEntry::where('event', 'scope.intake_submitted')->sole()->visible_to_client)->toBeTrue();

    $this->actingAs($this->admin)
        ->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('intake.submitted', true)
            ->where('intake.submittedByName', 'Anna de Vries')
            ->where('intake.featureLabels', ['Contact form', 'Online shop'])
            ->where('intake.budgetLabel', '€2,500–5,000'));
});

test('sending needs goals, a page and a budget', function () {
    $intake = askIntake($this);

    $this->actingAs($this->client)
        ->put(route('client.intakes.update', $intake), ['pages' => ['', ' '], 'submit' => true])
        ->assertSessionHasErrors(['goals', 'pages', 'budget']);

    expect($intake->refresh()->isSubmitted())->toBeFalse();
});

test('answers are validated', function (array $overrides, string $field) {
    $intake = askIntake($this);

    $this->actingAs($this->client)
        ->put(route('client.intakes.update', $intake), intakeAnswers($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'unknown budget' => [['budget' => 'a million'], 'budget'],
    'unknown feature' => [['features' => ['teleport']], 'features.0'],
    'deadline in the past' => [['deadline_on' => '2020-01-01'], 'deadline_on'],
    'not a date' => [['deadline_on' => 'soon'], 'deadline_on'],
    'long page name' => [['pages' => [str_repeat('a', 81)]], 'pages.0'],
    'too many pages' => [['pages' => array_fill(0, 31, 'Page')], 'pages'],
    'pages not a list' => [['pages' => 'Home'], 'pages'],
    'long goals' => [['goals' => str_repeat('a', 5001)], 'goals'],
]);

test('a sent intake is read-only for the client until the studio reopens it', function () {
    $intake = askIntake($this);

    $this->actingAs($this->client)
        ->put(route('client.intakes.update', $intake), [...intakeAnswers(), 'submit' => true])
        ->assertRedirect();

    $this->put(route('client.intakes.update', $intake), intakeAnswers(['goals' => 'Changed']))->assertForbidden();
    expect($intake->refresh()->goals)->not->toBe('Changed');

    // Only the studio reopens.
    $this->post(route('admin.intakes.reopen', $intake))->assertForbidden();

    $this->actingAs($this->admin)
        ->post(route('admin.intakes.reopen', $intake))
        ->assertRedirect(route('admin.scope.show', $this->project));

    expect($intake->refresh()->isSubmitted())->toBeFalse()
        ->and(intakeWaitingTitles($this->client))->toContain('Fill in the intake')
        ->and(ActivityEntry::where('event', 'scope.intake_reopened')->sole()->visible_to_client)->toBeTrue();

    $this->actingAs($this->client)
        ->put(route('client.intakes.update', $intake), intakeAnswers(['goals' => 'Changed']))
        ->assertRedirect();

    expect($intake->refresh()->goals)->toBe('Changed');
});

test('other organizations and workspaces get a 404, clients a 403 on studio pages', function () {
    $intake = Intake::factory()->for($this->project)->create();

    $neighbour = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($neighbour)->get(route('client.scope.show', $this->project))->assertNotFound();
    $this->put(route('client.intakes.update', $intake), intakeAnswers())->assertNotFound();
    expect(intakeWaitingTitles($neighbour))->toBe([]);

    $this->actingAs($this->client)->get(route('admin.scope.show', $this->project))->assertForbidden();
    $this->post(route('admin.intakes.store', $this->project))->assertForbidden();

    $stranger = User::factory()->admin()->create();
    $this->actingAs($stranger)->get(route('admin.scope.show', $this->project))->assertNotFound();
    $this->post(route('admin.intakes.store', $this->project))->assertNotFound();
    $this->post(route('admin.intakes.reopen', $intake))->assertNotFound();
});

test('an archived project hides its intake from the client', function () {
    Intake::factory()->for($this->project)->create();
    $this->project->forceFill(['archived_at' => now()])->save();

    $this->actingAs($this->client)->get(route('client.scope.show', $this->project))->assertNotFound();
    expect(intakeWaitingTitles($this->client))->toBe([]);
});
