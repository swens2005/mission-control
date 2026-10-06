<?php

use App\Enums\ChecklistOwner;
use App\Enums\ProjectPhase;
use App\Models\ActivityEntry;
use App\Models\ChecklistItem;
use App\Models\Launch;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Launch\DefaultChecklist;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Launch)->create(['name' => 'Bakery site']);
});

function clientOf(Organization $organization): User
{
    return User::factory()->client($organization)->create();
}

function launchFor(Project $project): Launch
{
    $launch = Launch::factory()->for($project)->create(['url' => 'https://bakery.example']);
    DefaultChecklist::addTo($launch);

    return $launch;
}

test('an admin prepares a launch with the default checklist', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('admin/launch/show')->where('launch', null));

    $this->post(route('admin.launch.store', $this->project), ['url' => 'https://bakery.example'])
        ->assertRedirect(route('admin.launch.show', $this->project));

    $launch = Launch::sole();
    $items = $launch->checklistItems;

    expect($launch->url)->toBe('https://bakery.example')
        ->and($launch->workspace_id)->toBe($this->admin->workspace_id)
        ->and($items->pluck('label')->all())->toBe(['404 page', 'Favicon', 'Backups', 'Forms tested', 'DNS TTL lowered', 'Analytics consent'])
        ->and($items->where('owner', ChecklistOwner::Client))->toHaveCount(3)
        ->and(ActivityEntry::where('event', 'launch.created')->value('visible_to_client'))->toBeTrue();

    $this->get(route('admin.launch.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->has('launch.checklist', 6)->where('launch.checklist.0.canToggle', true));
});

test('preparing twice does not create a second launch', function () {
    launchFor($this->project);

    $this->actingAs($this->admin)->post(route('admin.launch.store', $this->project), ['url' => 'https://other.example']);

    expect(Launch::count())->toBe(1)->and(Launch::sole()->url)->toBe('https://bakery.example');
});

test('the site URL must be http or https', function (string $url) {
    $this->actingAs($this->admin)
        ->post(route('admin.launch.store', $this->project), ['url' => $url])
        ->assertSessionHasErrors('url');
})->with(['ftp://bakery.example', 'javascript:alert(1)', 'file:///etc/passwd', 'not a url', '']);

test('an admin changes the site URL', function () {
    $launch = launchFor($this->project);

    $this->actingAs($this->admin)
        ->patch(route('admin.launch.update', $this->project), ['url' => 'https://new.example'])
        ->assertRedirect();

    expect($launch->fresh()->url)->toBe('https://new.example');
});

test('demo sandboxes can only point a launch at codelaunch.nl', function () {
    $this->admin->workspace->update(['is_sandbox' => true, 'expires_at' => now()->addDay()]);
    $this->actingAs($this->admin->fresh());

    $this->post(route('admin.launch.store', $this->project), ['url' => 'https://bakery.example'])
        ->assertSessionHasErrors('url');

    $this->post(route('admin.launch.store', $this->project), ['url' => 'https://codelaunch.nl'])
        ->assertSessionHasNoErrors();
});

test('an admin ticks and unticks an item, once each', function () {
    $item = launchFor($this->project)->checklistItems->first();
    $this->actingAs($this->admin);

    $this->put(route('admin.checklist.check', $item))->assertRedirect();
    $this->put(route('admin.checklist.check', $item));

    $item->refresh();
    expect($item->isChecked())->toBeTrue()
        ->and($item->checked_by_name)->toBe($this->admin->name)
        ->and(ActivityEntry::where('event', 'checklist.checked')->count())->toBe(1);

    $this->delete(route('admin.checklist.uncheck', $item));

    expect($item->fresh()->isChecked())->toBeFalse()
        ->and($item->fresh()->checked_by_name)->toBeNull();
});

test('an admin adds and removes items', function () {
    $launch = launchFor($this->project);
    $this->actingAs($this->admin);

    $this->post(route('admin.checklist.store', $this->project), ['label' => 'Cookie banner copy', 'owner' => 'client'])
        ->assertSessionHasNoErrors();

    $added = $launch->checklistItems()->get()->last();
    expect($added->label)->toBe('Cookie banner copy')
        ->and($added->owner)->toBe(ChecklistOwner::Client)
        ->and($added->position)->toBe(6);

    $this->post(route('admin.checklist.store', $this->project), ['label' => '', 'owner' => 'nobody'])
        ->assertSessionHasErrors(['label', 'owner']);

    $this->delete(route('admin.checklist.destroy', $added));
    expect($launch->checklistItems()->count())->toBe(6);
});

test('another workspace\'s launch and items are not found for an admin', function () {
    $other = Project::factory()->create();
    $item = launchFor($other)->checklistItems->first();
    $this->actingAs($this->admin);

    $this->get(route('admin.launch.show', $other))->assertNotFound();
    $this->post(route('admin.launch.store', $other), ['url' => 'https://x.example'])->assertNotFound();
    $this->put(route('admin.checklist.check', $item))->assertNotFound();
    $this->delete(route('admin.checklist.destroy', $item))->assertNotFound();

    expect(ChecklistItem::withoutGlobalScopes()->find($item->id)->isChecked())->toBeFalse();
});

test('a client sees the checklist and ticks only their own items', function () {
    $launch = launchFor($this->project);
    $client = clientOf($this->organization);
    $clientItem = $launch->checklistItems->firstWhere('owner', ChecklistOwner::Client);
    $studioItem = $launch->checklistItems->firstWhere('owner', ChecklistOwner::Studio);

    $this->actingAs($client)
        ->get(route('client.launch.show', $this->project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('client/launch/show')
            ->has('launch.checklist', 6)
            ->where('launch.checklist.0.canToggle', false)
            ->where('launch.checklist.3.canToggle', true));

    $this->put(route('client.checklist.check', $clientItem))->assertRedirect();
    $this->put(route('client.checklist.check', $studioItem))->assertForbidden();

    expect($clientItem->fresh()->checked_by_name)->toBe($client->name)
        ->and($studioItem->fresh()->isChecked())->toBeFalse()
        ->and(ActivityEntry::where('event', 'checklist.checked')->value('visible_to_client'))->toBeTrue();
});

test('a client of another organization gets 404', function () {
    $item = launchFor($this->project)->checklistItems->firstWhere('owner', ChecklistOwner::Client);
    $stranger = clientOf(Organization::factory()->for($this->admin->workspace)->create());

    $this->actingAs($stranger)->get(route('client.launch.show', $this->project))->assertNotFound();
    $this->actingAs($stranger)->put(route('client.checklist.check', $item))->assertNotFound();
});

test('a project without a launch has no client launch page', function () {
    $this->actingAs(clientOf($this->organization))
        ->get(route('client.launch.show', $this->project))
        ->assertNotFound();
});

test('clients cannot use the studio routes', function () {
    $item = launchFor($this->project)->checklistItems->first();

    $this->actingAs(clientOf($this->organization))
        ->put(route('admin.checklist.check', $item))
        ->assertForbidden();
});

test('open client items show up in Waiting on you', function () {
    $launch = launchFor($this->project);
    $client = clientOf($this->organization);
    $this->actingAs($client);

    $items = app(WaitingOnClient::class)->for($client);
    expect($items)->toHaveCount(1)
        ->and($items[0]->title)->toBe('Finish your launch checklist (3 items left)')
        ->and($items[0]->url)->toBe(route('client.launch.show', $this->project))
        ->and($items[0]->dueOn?->toDateString())->toBe($this->project->target_launch_on->toDateString());

    $launch->checklistItems->where('owner', ChecklistOwner::Client)->each(fn (ChecklistItem $item) => $item->check($client));

    expect(app(WaitingOnClient::class)->for($client))->toBeEmpty();
});

test('launched and archived projects are not waiting on the client', function (string $state) {
    launchFor($this->project);
    $state === 'launched'
        ? $this->project->update(['phase' => ProjectPhase::Launched])
        : $this->project->forceFill(['archived_at' => now()])->save();

    $client = clientOf($this->organization);
    $this->actingAs($client);

    expect(app(WaitingOnClient::class)->for($client))->toBeEmpty();
})->with(['launched', 'archived']);

test('a launch refuses a project from another workspace', function () {
    $launch = new Launch(['url' => 'https://x.example']);
    $launch->project_id = Project::factory()->create()->id;
    $launch->workspace_id = Workspace::factory()->create()->id;

    expect(fn () => $launch->save())->toThrow(LogicException::class);
});
