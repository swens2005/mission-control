<?php

use App\Enums\ProjectPhase;
use App\Models\Project;
use App\Models\Proposal;
use App\Support\Http\Resolver;
use Illuminate\Support\Facades\Http;

/*
 * Every autopilot tour, end to end in a real browser (story 33, ADR 0011).
 * The tour speed is 0 in tests (phpunit.xml), so a tour takes seconds. A tour that loses its way, a JavaScript error or a
 * CSP violation fails the test.
 */

beforeEach(function () {
    // Each tour makes its own sandbox; don't let the per-visitor limit
    // stop the next one.
    config(['demo.per_ip_per_hour' => 1000]);

    // Launch Control's checks: every host "resolves" to a public address
    // and gets a small, valid page back. CI never depends on the internet.
    app()->instance(Resolver::class, new class implements Resolver
    {
        public function resolve(string $host): array
        {
            return ['93.184.216.34'];
        }
    });

    Http::preventStrayRequests();
    Http::fake(['*' => Http::response(
        '<!doctype html><html lang="en"><head><title>Online pre-orders</title><meta name="description" content="Order bread ahead."></head><body><h1>Pre-orders</h1></body></html>',
        200,
        ['Content-Type' => 'text/html'],
    )]);
});

/**
 * Starts a tour from the autopilot page and waits until it ends or gets
 * lost. Returns the control bar's last text.
 */
function playTour(string $id, ?string $remove = null): array
{
    // Speed 0 (phpunit.xml) already makes every movement and wait instant.
    $page = visit('/autopilot');

    if ($remove !== null) {
        // Simulates a renamed data-tour attribute: whenever the element
        // shows up, it loses its hook.
        $page->script(sprintf(<<<'JS'
            new MutationObserver(() =>
                document.querySelectorAll('[data-tour="%s"]').forEach((el) => el.removeAttribute('data-tour')),
            ).observe(document.body, { subtree: true, childList: true });
            JS, $remove));
    }

    $page->click("[data-tour=\"start-{$id}\"]");

    $bar = '';

    for ($i = 0; $i < 240; $i++) {
        $bar = (string) $page->script(
            "document.querySelector('[aria-label=\"Autopilot\"]')?.innerText ?? ''",
        );

        if (str_contains($bar, 'THE END') || str_contains($bar, 'Lost my way')) {
            break;
        }

        $page->wait(0.25);
    }

    return [$page, $bar];
}

test('the :dataset tour runs end to end', function (string $id) {
    [$page, $bar] = playTour($id);

    expect($bar)->not->toContain('Lost my way')
        ->and($bar)->toContain('THE END');

    $page->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
})->with([
    'full story' => 'full-story',
    'studio' => 'studio',
    'client' => 'client',
    'Scope chapter' => 'chapter-scope',
    'Palette Lab chapter' => 'chapter-palette',
    'Proofmark chapter' => 'chapter-proofmark',
    'Launch Control chapter' => 'chapter-launch',
]);

test('a missing target makes the tour lose its way, which fails the check', function () {
    [, $bar] = playTour('chapter-scope', remove: 'changes-request');

    expect($bar)->toContain('Lost my way at step')
        ->and($bar)->not->toContain('THE END');
});

test('the full story really sends and accepts v2 and moves the project on', function () {
    [, $bar] = playTour('full-story');

    expect($bar)->toContain('THE END');

    $project = Project::withoutGlobalScopes()->where('name', 'Seasonal menu pages')->latest('id')->firstOrFail();
    $v2 = Proposal::withoutGlobalScopes()->where('project_id', $project->id)->where('number', 2)->sole();

    expect($v2->isSent())->toBeTrue()
        ->and($v2->isAccepted())->toBeTrue()
        ->and($v2->accepted_signature)->toBe('Anna de Vries')
        ->and($project->phase)->toBe(ProjectPhase::Palette);
});
