<?php

use App\Enums\ChecklistOwner;
use App\Enums\CheckStatus;
use App\Enums\ProjectPhase;
use App\Models\ActivityEntry;
use App\Models\ChecklistItem;
use App\Models\CheckRun;
use App\Models\CheckRunResult;
use App\Models\Launch;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Signoff;
use App\Models\User;
use App\Support\Http\Resolver;
use App\Support\Launch\Checks\CheckRegistry;
use App\Support\Waiting\WaitingOnClient;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Sam Visser']);
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->client = User::factory()->client($this->organization)->create(['name' => 'Anna de Vries']);
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Launch)->create();
    $this->launch = Launch::factory()->for($this->project)->create(['url' => 'https://bakery.example']);

    $this->item = ChecklistItem::factory()->for($this->launch)->checked()->create(['label' => 'Backups']);
    ChecklistItem::factory()->for($this->launch)->client()->checked()->create(['label' => 'Forms tested']);
});

/**
 * A stored run where every check has the given status (default: all pass).
 */
function storeRun(Launch $launch, CheckStatus $status = CheckStatus::Pass, ?string $url = null): CheckRun
{
    $run = new CheckRun;
    $run->forceFill([
        'workspace_id' => $launch->workspace_id,
        'launch_id' => $launch->id,
        'run_by_name' => 'Sam Visser',
        'url' => $url ?? $launch->url,
        'passed' => $status === CheckStatus::Pass ? 10 : 0,
        'failed' => $status === CheckStatus::Fail ? 10 : 0,
    ])->save();

    foreach (array_keys(CheckRegistry::labels()) as $position => $key) {
        (new CheckRunResult)->forceFill([
            'workspace_id' => $launch->workspace_id,
            'check_run_id' => $run->id,
            'check_key' => $key,
            'status' => $status,
            'message' => 'Fixture.',
            'position' => $position,
        ])->save();
    }

    return $run;
}

test('the board says NO-GO and explains why until everything is ready', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('launch.board.status', 'NO-GO')
            ->where('launch.board.headline', 'Automated checks: not run yet.')
            ->where('launch.board.canSign', false));
});

test('studio and client sign, the board says GO, and the project launches', function () {
    storeRun($this->launch);

    $this->actingAs($this->admin)
        ->post(route('admin.signoffs.store', $this->project), ['name' => ' sam  visser '])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($this->client)
        ->get(route('client.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('launch.board.status', 'CLEAR')
            ->where('launch.board.canSign', true)
            ->where('launch.board.signAs', 'Client'));

    $this->post(route('client.signoffs.store', $this->project), ['name' => 'Anna de Vries'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $signoff = Signoff::where('role', ChecklistOwner::Client)->sole();
    expect($signoff->name_typed)->toBe('Anna de Vries')
        ->and($signoff->ip)->toBe('127.0.0.1')
        ->and($signoff->user_id)->toBe($this->client->id);

    $this->actingAs($this->admin)
        ->get(route('admin.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('launch.board.status', 'GO')
            ->where('launch.board.canMarkLaunched', true));

    $this->post(route('admin.launch.launched', $this->project))->assertRedirect();

    expect($this->project->fresh()->phase)->toBe(ProjectPhase::Launched)
        ->and(ActivityEntry::where('event', 'launch.signed')->count())->toBe(2);
});

test('signing is refused while the board is not clear', function () {
    storeRun($this->launch, CheckStatus::Fail);

    $this->actingAs($this->admin)
        ->post(route('admin.signoffs.store', $this->project), ['name' => 'Sam Visser'])
        ->assertSessionHasErrors('name');

    expect(Signoff::count())->toBe(0);
});

test('the typed name must be the signer\'s own', function () {
    storeRun($this->launch);

    $this->actingAs($this->admin)
        ->post(route('admin.signoffs.store', $this->project), ['name' => 'Someone Else'])
        ->assertSessionHasErrors('name');

    expect(Signoff::count())->toBe(0);
});

test('each side signs once', function () {
    storeRun($this->launch);
    $colleague = User::factory()->admin()->create(['workspace_id' => $this->admin->workspace_id, 'name' => 'Kim de Boer']);

    $this->actingAs($this->admin)->post(route('admin.signoffs.store', $this->project), ['name' => 'Sam Visser']);
    $this->actingAs($colleague)->post(route('admin.signoffs.store', $this->project), ['name' => 'Kim de Boer'])
        ->assertSessionHasErrors('name');

    expect(Signoff::count())->toBe(1);
});

test('a client of another organization cannot sign', function () {
    storeRun($this->launch);
    $stranger = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($stranger)
        ->post(route('client.signoffs.store', $this->project), ['name' => $stranger->name])
        ->assertNotFound();
});

test('sign-offs are voided, and kept, when the board stops being clear', function (Closure $change, string $reason) {
    storeRun($this->launch);
    $this->actingAs($this->admin)->post(route('admin.signoffs.store', $this->project), ['name' => 'Sam Visser']);
    $this->actingAs($this->client)->post(route('client.signoffs.store', $this->project), ['name' => 'Anna de Vries']);

    $change($this);

    expect(Signoff::active()->count())->toBe(0)
        ->and(Signoff::count())->toBe(2)
        ->and(Signoff::first()->void_reason)->toBe($reason)
        ->and(ActivityEntry::where('event', 'launch.signoffs_voided')->value('visible_to_client'))->toBeTrue();
})->with([
    'an item is unticked' => [fn ($test) => $test->actingAs($test->admin)->delete(route('admin.checklist.uncheck', $test->item)), 'Backups was unticked.'],
    'an item is added' => [fn ($test) => $test->actingAs($test->admin)->post(route('admin.checklist.store', $test->project), ['label' => 'Redirects', 'owner' => 'studio']), 'Redirects was added to the checklist.'],
    'the URL changes' => [fn ($test) => $test->actingAs($test->admin)->patch(route('admin.launch.update', $test->project), ['url' => 'https://new.example']), 'The site URL changed.'],
]);

test('a new check run that is not clear voids the sign-offs', function () {
    storeRun($this->launch);
    $this->actingAs($this->admin)->post(route('admin.signoffs.store', $this->project), ['name' => 'Sam Visser']);

    // A real run through the endpoint, against a bare page that fails most checks.
    app()->instance(Resolver::class, new class implements Resolver
    {
        public function resolve(string $host): array
        {
            return ['93.184.216.34'];
        }
    });
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('<p>Coming soon</p>', 200, ['Content-Type' => 'text/html'])]);

    $this->post(route('admin.checks.store', $this->project))->assertSessionHasNoErrors();

    expect(Signoff::active()->count())->toBe(0)
        ->and(Signoff::sole()->void_reason)->toBe("The latest check run isn't all clear.");
});

test('a change that keeps the board clear keeps the sign-offs', function () {
    storeRun($this->launch);
    $this->actingAs($this->admin)->post(route('admin.signoffs.store', $this->project), ['name' => 'Sam Visser']);

    // Removing an item can only make the board more ready.
    $this->delete(route('admin.checklist.destroy', $this->item));

    expect(Signoff::active()->count())->toBe(1);
});

test('marking launched is refused unless the board says GO', function () {
    storeRun($this->launch);
    $this->actingAs($this->admin)->post(route('admin.signoffs.store', $this->project), ['name' => 'Sam Visser']);

    $this->post(route('admin.launch.launched', $this->project))->assertSessionHasErrors('launch');

    expect($this->project->fresh()->phase)->toBe(ProjectPhase::Launch);
});

test('clients cannot mark a project launched', function () {
    $this->actingAs($this->client)->post(route('admin.launch.launched', $this->project))->assertForbidden();
});

test('"Sign off the launch" waits on the client only while the board is clear and unsigned', function () {
    $waiting = fn () => collect(app(WaitingOnClient::class)->for($this->client))->pluck('title')->all();
    $this->actingAs($this->client);

    expect($waiting())->not->toContain('Sign off the launch');

    storeRun($this->launch);
    expect($waiting())->toContain('Sign off the launch');

    $this->post(route('client.signoffs.store', $this->project), ['name' => 'Anna de Vries']);
    expect($waiting())->not->toContain('Sign off the launch');
});
