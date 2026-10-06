<?php

use App\Enums\ColorRole;
use App\Enums\ProjectPhase;
use App\Models\ActivityEntry;
use App\Models\BrandKit;
use App\Models\Color;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Sandbox\SandboxFactory;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Palette)->create(['name' => 'Café corner']);
    $this->client = User::factory()->client($this->organization)->create(['name' => 'Anna de Vries']);
    $this->kit = BrandKit::factory()->for($this->project)->create();
    Color::factory()->for($this->kit, 'kit')->hex('#4a2c1a')->create(['name' => 'Brown', 'position' => 0]);
    Color::factory()->for($this->kit, 'kit')->hex('#d9a441', ColorRole::Shape)->create(['name' => 'Wheat', 'position' => 1]);
    Color::factory()->for($this->kit, 'kit')->hex('#f7efe3', ColorRole::Surface)->create(['name' => 'Cream', 'position' => 2]);
});

function kitWaitingTitles(User $client): array
{
    return array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($client));
}

test('the studio shares the kit and the client sees the style guide', function () {
    $this->actingAs($this->client)->get(route('client.palette.show', $this->project))->assertNotFound();

    $this->actingAs($this->admin)
        ->post(route('admin.kits.share', $this->kit))
        ->assertRedirect(route('admin.palette.show', $this->project));

    expect($this->kit->refresh()->shared_at)->not->toBeNull()
        ->and(ActivityEntry::where('event', 'palette.kit_shared')->sole()->visible_to_client)->toBeTrue();

    $this->actingAs($this->client)
        ->get(route('client.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('client/palette/show')
            ->has('guide.colors', 3)
            // Only text pairs that pass; shapes are not text.
            ->has('guide.pairs', 1)
            ->where('guide.pairs.0.text', 'Brown')
            ->where('guide.pairs.0.surface', 'Cream')
            ->where('guide.pairs.0.gradeLabel', 'AAA')
            ->has('guide.type.scale')
            ->missing('guide.matrix'));

    $this->get(route('client.projects.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('hasPalette', true));
});

test('a kit without colors cannot be shared', function () {
    $empty = BrandKit::factory()->for(Project::factory()->for($this->organization))->create();

    $this->actingAs($this->admin)->post(route('admin.kits.share', $empty))->assertSessionHasErrors('share');
});

test('the client approves once, which locks the kit and clears the waiting item', function () {
    $this->kit->forceFill(['shared_at' => now()])->save();

    expect(kitWaitingTitles($this->client))->toContain('Approve the brand kit');

    $this->actingAs($this->client)
        ->post(route('client.kits.approve', $this->kit), [], ['REMOTE_ADDR' => '203.0.113.7'])
        ->assertRedirect(route('client.palette.show', $this->project));

    $this->kit->refresh();

    expect($this->kit->approved_by_name)->toBe('Anna de Vries')
        ->and($this->kit->approved_ip)->toBe('203.0.113.7')
        ->and(kitWaitingTitles($this->client))->not->toContain('Approve the brand kit')
        ->and(ActivityEntry::where('event', 'palette.kit_approved')->sole()->visible_to_client)->toBeTrue();

    $this->post(route('client.kits.approve', $this->kit))->assertForbidden();

    $this->actingAs($this->admin)
        ->post(route('admin.colors.store', $this->kit), ['name' => 'Rosé', 'role' => 'shape', 'value' => '#e98f9b'])
        ->assertForbidden();
});

test('a revision unlocks the kit and asks the client again', function () {
    $this->kit->forceFill(['shared_at' => now(), 'approved_at' => now(), 'approved_by_name' => 'Anna de Vries'])->save();

    $this->actingAs($this->admin)->post(route('admin.kits.revise', $this->kit))->assertRedirect();

    expect($this->kit->refresh()->isApproved())->toBeFalse()
        ->and($this->kit->approved_by_name)->toBeNull()
        ->and(kitWaitingTitles($this->client))->toContain('Approve the brand kit')
        ->and(ActivityEntry::where('event', 'palette.kit_revised')->sole()->visible_to_client)->toBeTrue();

    $this->post(route('admin.colors.store', $this->kit), ['name' => 'Rosé', 'role' => 'shape', 'value' => '#e98f9b'])->assertRedirect();
});

test('other organizations, unshared kits and the studio cannot approve', function () {
    $other = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($this->client)->post(route('client.kits.approve', $this->kit))->assertNotFound();

    $this->kit->forceFill(['shared_at' => now()])->save();

    $this->actingAs($other)->get(route('client.palette.show', $this->project))->assertNotFound();
    $this->actingAs($other)->post(route('client.kits.approve', $this->kit))->assertNotFound();
    $this->actingAs($this->admin)->post(route('client.kits.approve', $this->kit))->assertForbidden();

    expect($this->kit->refresh()->isApproved())->toBeFalse();
});

test('the public link works without login only while it is on', function () {
    $this->actingAs($this->admin)->post(route('admin.kits.public-link.store', $this->kit))->assertSessionHasErrors('public_link');

    $this->kit->forceFill(['shared_at' => now()])->save();
    $this->post(route('admin.kits.public-link.store', $this->kit))->assertRedirect();

    $token = $this->kit->refresh()->public_token;
    expect($token)->toHaveLength(40);

    auth()->logout();

    $this->get(route('style-guide.show', $token))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertInertia(fn (Assert $page) => $page->component('public/style-guide')
            ->where('projectName', 'Café corner')
            ->has('guide.colors', 3)
            ->missing('guide.matrix')
            ->missing('kit'));

    // Turning it on again gives a new token; the old one is dead.
    $this->actingAs($this->admin)->post(route('admin.kits.public-link.store', $this->kit));
    $newToken = $this->kit->refresh()->public_token;
    expect($newToken)->not->toBe($token);

    auth()->logout();
    $this->get(route('style-guide.show', $token))->assertNotFound();

    $this->actingAs($this->admin)->delete(route('admin.kits.public-link.destroy', $this->kit))->assertRedirect();
    auth()->logout();
    $this->get(route('style-guide.show', $newToken))->assertNotFound();
});

test('guessing tokens gets 404', function (string $token) {
    $this->get(route('style-guide.show', $token))->assertNotFound();
})->with(['short', str_repeat('a', 40), str_repeat('-', 40)]);

test('only the studio of the workspace manages sharing', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.kits.share', $this->kit))
        ->assertNotFound();

    $this->actingAs($this->client)->post(route('admin.kits.share', $this->kit))->assertForbidden();
});

test('a fresh sandbox has both demo kits', function () {
    $sandbox = (new SandboxFactory)->create();
    $kits = BrandKit::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->with('project')->get()->keyBy('project.name');

    expect($kits->keys()->sort()->values()->all())->toBe(['Café corner', 'codelaunch.nl'])
        ->and($kits['Café corner']->shared_at)->not->toBeNull()
        ->and($kits['codelaunch.nl']->shared_at)->toBeNull()
        ->and(kitWaitingTitles($sandbox->client))->toContain('Approve the brand kit');

    // codelaunch.nl: the CV label blue fails everywhere, the brand green
    // only on the console screen. Every Café corner text pair passes.
    $this->actingAs($sandbox->admin);
    $codelaunch = Project::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('name', 'codelaunch.nl')->sole();

    $this->get(route('admin.palette.show', $codelaunch))
        ->assertInertia(fn (Assert $page) => $page->where('kit.matrix.failing', 5)
            ->where('kit.matrix.rows.2.name', 'Green')
            ->where('kit.matrix.rows.2.cells.3.gradeLabel', 'AA large only')
            ->where('kit.matrix.rows.3.name', 'Screen label')
            ->where('kit.matrix.rows.3.cells.3.fix.hex', '#3367ab'));

    $cafe = Project::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('name', 'Café corner')->sole();

    $this->get(route('admin.palette.show', $cafe))
        ->assertInertia(fn (Assert $page) => $page->where('kit.matrix.failing', 0));
});
