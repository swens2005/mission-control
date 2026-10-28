<?php

use App\Models\BrandKit;
use App\Models\Launch;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Sandbox\Sandbox;
use App\Support\Sandbox\SandboxFactory;
use Illuminate\Support\Str;

/*
 * axe on every page a visitor can reach (story 35). The plugin injects axe
 * as a Playwright init script, which the page's CSP doesn't apply to, so
 * the CSP stays as strict as in production. Every page is clean at every
 * level (minor included), so any new finding fails.
 */

beforeEach(function () {
    $this->sandbox = app(SandboxFactory::class)->create();
});

/**
 * The demo project that has the given module's data.
 *
 * @param  class-string<Launch|ReviewRound|Proposal|BrandKit>  $model
 */
function projectWith(Sandbox $sandbox, string $model): Project
{
    return $model::withoutGlobalScopes()
        ->where('workspace_id', $sandbox->workspace->id)
        ->oldest('id')
        ->firstOrFail()
        ->project()
        ->withoutGlobalScopes()
        ->firstOrFail();
}

/**
 * @return array<string, string>
 */
function studioPages(Sandbox $sandbox): array
{
    $launch = projectWith($sandbox, Launch::class);
    $proofmark = projectWith($sandbox, ReviewRound::class);
    $palette = projectWith($sandbox, BrandKit::class);
    $scope = projectWith($sandbox, Proposal::class);
    $proposal = Proposal::withoutGlobalScopes()->where('project_id', $scope->id)->oldest('id')->firstOrFail();
    $organization = $scope->organization()->withoutGlobalScopes()->firstOrFail();

    return [
        'studio home' => '/admin',
        'organizations' => '/admin/organizations',
        'new organization' => '/admin/organizations/create',
        'organization' => "/admin/organizations/{$organization->id}",
        'edit organization' => "/admin/organizations/{$organization->id}/edit",
        'projects' => '/admin/projects',
        'new project' => '/admin/projects/create',
        'project' => "/admin/projects/{$scope->id}",
        'edit project' => "/admin/projects/{$scope->id}/edit",
        'activity' => '/admin/activity',
        'line-item library' => '/admin/scope/library',
        'Scope' => "/admin/projects/{$scope->id}/scope",
        'proposal version' => "/admin/proposals/{$proposal->id}",
        'Palette Lab' => "/admin/projects/{$palette->id}/palette",
        'Proofmark' => "/admin/projects/{$proofmark->id}/proofmark",
        'Launch Control' => "/admin/projects/{$launch->id}/launch",
        'profile settings' => '/settings/profile',
        'security settings' => '/settings/security',
    ];
}

/**
 * @return array<string, string>
 */
function launchpadPages(Sandbox $sandbox): array
{
    $launch = projectWith($sandbox, Launch::class);
    $proofmark = projectWith($sandbox, ReviewRound::class);
    $palette = projectWith($sandbox, BrandKit::class);
    $scope = projectWith($sandbox, Proposal::class);
    $proposal = Proposal::withoutGlobalScopes()->where('project_id', $scope->id)->oldest('id')->firstOrFail();

    return [
        'Launchpad home' => '/client',
        'client project' => "/client/projects/{$scope->id}",
        'client Scope' => "/client/projects/{$scope->id}/scope",
        'client proposal version' => "/client/proposals/{$proposal->id}",
        'client Palette' => "/client/projects/{$palette->id}/palette",
        'client Proofmark' => "/client/projects/{$proofmark->id}/proofmark",
        'client Launch' => "/client/projects/{$launch->id}/launch",
        'client profile settings' => '/settings/profile',
    ];
}

/**
 * Every axe violation on the page, at any level, one line each.
 *
 * @return list<string>
 */
function axeViolations(mixed $page, string $name): array
{
    $violations = $page->script(<<<'JS'
        (async () => (await window.axe.run()).violations
            .map((v) => `${v.id}: ${v.help} -> ${v.nodes.map((n) => n.target.join(' ')).slice(0, 5).join(' | ')}`))()
        JS);

    return array_map(fn (string $line) => "[{$name}] {$line}", is_array($violations) ? $violations : []);
}

test('every studio page passes axe', function () {
    $this->actingAs($this->sandbox->admin);

    $found = [];

    foreach (studioPages($this->sandbox) as $name => $url) {
        $page = visit($url);
        $page->assertNoJavaScriptErrors();
        $found = [...$found, ...axeViolations($page, $name)];
    }

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('every Launchpad page passes axe', function () {
    $this->actingAs($this->sandbox->client);

    $found = [];

    foreach (launchpadPages($this->sandbox) as $name => $url) {
        $page = visit($url);
        $page->assertNoJavaScriptErrors();
        $found = [...$found, ...axeViolations($page, $name)];
    }

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('the public pages pass axe', function () {
    $kit = BrandKit::withoutGlobalScopes()->where('workspace_id', $this->sandbox->workspace->id)->whereNotNull('shared_at')->firstOrFail();
    $kit->public_token = Str::random(40);
    $kit->save();

    $found = [];

    foreach ([
        'login' => '/login',
        'forgot password' => '/forgot-password',
        'autopilot' => '/autopilot',
        'public style guide' => "/style-guide/{$kit->public_token}",
    ] as $name => $url) {
        $page = visit($url);
        $page->assertNoJavaScriptErrors();
        $found = [...$found, ...axeViolations($page, $name)];
    }

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('the login page with fresh demo credentials passes axe', function () {
    config(['demo.per_ip_per_hour' => 1000]);

    $page = visit('/login')
        ->press('Take the controls')
        ->assertSee('Your demo studio is ready');

    $found = axeViolations($page, 'login with demo credentials');

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('the autopilot bar passes axe while a tour is running', function () {
    // Real speed, so the bar stays up long enough to check.
    config(['tour.speed' => 100]);

    $page = visit('/autopilot');
    $page->click('[data-tour="start-studio"]');
    $page->assertSee('Pause');
    $page->click('Pause');
    $page->assertSee('Resume');

    $found = axeViolations($page, 'autopilot bar');

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('the two-factor setup dialog passes axe', function () {
    // Sandbox users can't turn on 2FA, so this is a regular studio admin,
    // signed in through the form so password confirmation sticks.
    $admin = User::factory()->admin()->create();

    $page = visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->press('Log in')
        ->assertPathIs('/admin')
        ->navigate('/settings/security')
        ->assertSee('This is a secure area');

    $found = axeViolations($page, 'confirm password');

    $page->type('password', 'password')
        ->click('[data-test="confirm-password-button"]')
        ->assertSee('Two-factor authentication')
        ->click('Enable 2FA')
        ->assertSee('Enable two-factor authentication')
        ->assertPresent('#two-factor-setup-key');

    $found = [...$found, ...axeViolations($page, '2FA setup')];

    $page->click('Continue')->assertSee('Verify authentication code');

    $found = [...$found, ...axeViolations($page, '2FA verify')];

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('the two-factor login challenge passes axe', function () {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $page = visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->press('Log in')
        ->assertPathIs('/two-factor-challenge');

    $found = axeViolations($page, '2FA code');

    $page->press('login using a recovery code')->assertPresent('#recovery_code');

    $found = [...$found, ...axeViolations($page, '2FA recovery code')];

    expect($found)->toBeEmpty(implode("\n", $found));
});

/**
 * Pages that scroll sideways at 320 px (WCAG 1.4.10, reflow; story 36).
 *
 * @param  array<string, string>  $pages
 * @return list<string>
 */
function sidewaysAt320(array $pages): array
{
    $found = [];

    foreach ($pages as $name => $url) {
        $page = visit($url)->resize(320, 800);
        $width = $page->script('document.documentElement.scrollWidth - document.documentElement.clientWidth');

        if ($width > 0) {
            $found[] = "[{$name}] scrolls sideways by {$width}px at 320px";
        }
    }

    return $found;
}

test('no page scrolls sideways at 320 px', function () {
    $this->actingAs($this->sandbox->admin);
    $found = sidewaysAt320(studioPages($this->sandbox));

    $this->actingAs($this->sandbox->client);
    $found = [...$found, ...sidewaysAt320(launchpadPages($this->sandbox))];

    expect($found)->toBeEmpty(implode("\n", $found));
});

test('Esc ends a tour and puts focus on the page', function () {
    config(['tour.speed' => 100]);

    $page = visit('/autopilot');
    $page->click('[data-tour="start-studio"]')
        ->assertSee('Pause')
        ->click('Pause')
        ->assertSee('Resume');

    // Esc pressed with focus on one of the bar's own buttons.
    $page->keys('[aria-label="Autopilot"] [aria-keyshortcuts="Escape"]', 'Escape');
    $page->wait(0.2);

    expect($page->script("document.querySelector('[aria-label=\"Autopilot\"]') === null"))->toBeTrue()
        ->and($page->script('document.activeElement?.id'))->toBe('main');
});

test('the sweep catches a planted issue', function () {
    $page = visit('/login');
    $page->script("document.body.append(Object.assign(document.createElement('img'), { src: 'data:image/gif;base64,R0lGODlhAQABAAAAACw=' }))");

    expect(axeViolations($page, 'login'))->toContain('[login] image-alt: Images must have alternative text -> img');
});
