<?php

use App\Enums\CheckStatus;
use App\Enums\ProjectPhase;
use App\Http\Controllers\Admin\CheckRunController;
use App\Models\ActivityEntry;
use App\Models\CheckRun;
use App\Models\CheckWaiver;
use App\Models\Launch;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Http\Resolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Every host "resolves" to a public address; responses are faked.
    app()->instance(Resolver::class, new class implements Resolver
    {
        public function resolve(string $host): array
        {
            return ['93.184.216.34'];
        }
    });

    Http::preventStrayRequests();
    Http::fake(['*' => Http::response(
        '<!doctype html><html><head><title>Bakkerij de Vries</title></head><body><h1>Brood</h1></body></html>',
        200,
        ['Content-Type' => 'text/html', 'X-Content-Type-Options' => 'nosniff'],
    )]);

    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Launch)->create();
    $this->launch = Launch::factory()->for($this->project)->create(['url' => 'https://bakery.example']);
});

test('running the checks stores a run with every result', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.checks.store', $this->project))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $run = CheckRun::sole();

    expect($run->results)->toHaveCount(10)
        ->and($run->run_by_name)->toBe($this->admin->name)
        ->and($run->url)->toBe('https://bakery.example')
        ->and($run->passed + $run->warned + $run->failed + $run->skipped)->toBe(10)
        ->and($run->results->firstWhere('check_key', 'headings')->status)->toBe(CheckStatus::Pass)
        ->and(ActivityEntry::where('event', 'launch.checked')->value('visible_to_client'))->toBeTrue();

    $this->get(route('admin.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('launch.checks.results', 10)
            ->where('launch.checks.latestRun.ranBy', $this->admin->name)
            ->where('launch.checks.previousRunAt', null));
});

test('a waiver needs a reason and survives a new run', function () {
    $this->actingAs($this->admin);
    $this->post(route('admin.checks.store', $this->project));

    $this->post(route('admin.waivers.store', [$this->project, 'og-image']), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    $this->post(route('admin.waivers.store', [$this->project, 'og-image']), ['reason' => 'Social image comes in phase two.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->post(route('admin.checks.store', $this->project));

    $this->get(route('admin.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('launch.checks.results.5.key', 'og-image')
            ->where('launch.checks.results.5.waiver.reason', 'Social image comes in phase two.')
            ->whereNot('launch.checks.previousRunAt', null));

    $this->delete(route('admin.waivers.destroy', [$this->project, 'og-image']));

    expect(CheckWaiver::count())->toBe(0)
        ->and(ActivityEntry::where('event', 'check.unwaived')->count())->toBe(1);
});

test('waiving an unknown check is not found', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.waivers.store', [$this->project, 'nonsense']), ['reason' => 'Because reasons.'])
        ->assertNotFound();
});

test('runs are rate limited per user and per launch', function () {
    $this->actingAs($this->admin);

    foreach (range(1, CheckRunController::RUNS_PER_WINDOW) as $i) {
        $this->post(route('admin.checks.store', $this->project))->assertSessionHasNoErrors();
    }

    $this->post(route('admin.checks.store', $this->project))->assertSessionHasErrors('checks');
    expect(CheckRun::count())->toBe(CheckRunController::RUNS_PER_WINDOW);

    // A colleague hits the per-launch limit too.
    $colleague = User::factory()->admin()->create(['workspace_id' => $this->admin->workspace_id]);
    $this->actingAs($colleague)->post(route('admin.checks.store', $this->project))->assertSessionHasErrors('checks');
});

test('demo sandboxes only check codelaunch.nl', function () {
    $this->admin->workspace->update(['is_sandbox' => true, 'expires_at' => now()->addDay()]);

    $this->actingAs($this->admin->fresh())
        ->post(route('admin.checks.store', $this->project))
        ->assertSessionHasErrors('checks');

    expect(CheckRun::count())->toBe(0);
    Http::assertNothingSent();
});

test('clients see results but cannot run or waive', function () {
    $client = User::factory()->client($this->organization)->create();
    $this->actingAs($this->admin)->post(route('admin.checks.store', $this->project));

    $this->actingAs($client)
        ->get(route('client.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->has('launch.checks.results', 10));

    $this->post(route('admin.checks.store', $this->project))->assertForbidden();
    $this->post(route('admin.waivers.store', [$this->project, 'csp']), ['reason' => 'Trust me on this.'])->assertForbidden();
});

test('another workspace\'s launch is not found', function () {
    $other = Project::factory()->create();
    Launch::factory()->for($other)->create();

    $this->actingAs($this->admin)->post(route('admin.checks.store', $other))->assertNotFound();
    $this->actingAs($this->admin)->post(route('admin.waivers.store', [$other, 'csp']), ['reason' => 'Not mine anyway.'])->assertNotFound();

    expect(CheckRun::withoutGlobalScopes()->count())->toBe(0);
});

afterEach(function () {
    RateLimiter::clear('launch-checks:user:'.test()->admin->id);
});
