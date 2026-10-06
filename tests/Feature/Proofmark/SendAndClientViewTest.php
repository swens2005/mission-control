<?php

use App\Enums\ProjectPhase;
use App\Enums\RoundStatus;
use App\Models\ActivityEntry;
use App\Models\Design;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Waiting\WaitingOnClient;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TestImages;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)
        ->create(['name' => 'Wedding cakes', 'target_launch_on' => now()->addWeeks(3)]);
    $this->client = User::factory()->client($this->organization)->create();
});

function roundWithDesign(Project $project, ?RoundStatus $status = null): ReviewRound
{
    $round = ReviewRound::factory()->for($project)->create(['status' => $status ?? RoundStatus::Draft]);
    Design::factory()->for($round, 'round')->create(['title' => 'Home, desktop']);

    return $round;
}

function waitingTitles(User $client): array
{
    return array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($client));
}

test('sending a draft puts it in review and records it for the client', function () {
    $round = roundWithDesign($this->project);

    $this->actingAs($this->admin)
        ->post(route('admin.rounds.send', $round))
        ->assertRedirect(route('admin.proofmark.show', [$this->project, 'round' => 1]));

    $round->refresh();
    $entry = ActivityEntry::where('event', 'proofmark.round_sent')->sole();

    expect($round->status)->toBe(RoundStatus::InReview)
        ->and($round->sent_at)->not->toBeNull()
        ->and($entry->visible_to_client)->toBeTrue()
        ->and($entry->description())->toBe('sent design round v1 of Wedding cakes for review');
});

test('a round without designs cannot be sent', function () {
    $round = ReviewRound::factory()->for($this->project)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.rounds.send', $round))
        ->assertRedirect()
        ->assertSessionHasErrors('send');

    expect($round->refresh()->status)->toBe(RoundStatus::Draft);
});

test('a sent round is frozen and cannot be sent again', function () {
    $round = roundWithDesign($this->project);

    $this->actingAs($this->admin)->post(route('admin.rounds.send', $round));

    $this->post(route('admin.rounds.send', $round))->assertForbidden();
    $this->post(route('admin.designs.store', $round), ['title' => 'x', 'image' => TestImages::upload()])->assertForbidden();
    $this->delete(route('admin.designs.destroy', $round->designs()->first()))->assertForbidden();
});

test('sending v2 supersedes v1', function () {
    $v1 = roundWithDesign($this->project, RoundStatus::InReview);
    $v2 = roundWithDesign($this->project);

    $this->actingAs($this->admin)->post(route('admin.rounds.send', $v2));

    expect($v1->refresh()->status)->toBe(RoundStatus::Superseded)
        ->and($v2->refresh()->status)->toBe(RoundStatus::InReview)
        ->and(ActivityEntry::where('event', 'proofmark.round_sent')->sole()->description())
        ->toBe('sent design round v2 of Wedding cakes for review, replacing v1');
});

test('an approved round stays approved when the next one is sent', function () {
    $v1 = roundWithDesign($this->project, RoundStatus::Approved);
    $v2 = roundWithDesign($this->project);

    $this->actingAs($this->admin)->post(route('admin.rounds.send', $v2));

    expect($v1->refresh()->status)->toBe(RoundStatus::Approved);
});

test('the client sees sent rounds only, newest first', function () {
    roundWithDesign($this->project, RoundStatus::Superseded);
    roundWithDesign($this->project, RoundStatus::InReview);
    $draft = roundWithDesign($this->project);

    $this->actingAs($this->client)
        ->get(route('client.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('client/proofmark/show')
            ->has('rounds', 2)
            ->where('rounds.0.label', 'v2')
            ->where('round.statusLabel', 'In review')
            ->where('round.designs.0.title', 'Home, desktop'));

    // Asking for the draft by number falls back to the newest sent round.
    $this->get(route('client.proofmark.show', [$this->project, 'round' => $draft->number]))
        ->assertInertia(fn (Assert $page) => $page->where('round.label', 'v2'));

    $this->get(route('client.projects.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('hasProofmark', true));
});

test('a client without sent rounds gets 404 and no link', function () {
    roundWithDesign($this->project);

    $this->actingAs($this->client)->get(route('client.proofmark.show', $this->project))->assertNotFound();
    $this->get(route('client.projects.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('hasProofmark', false));
});

test('a client of another organization gets 404', function () {
    roundWithDesign($this->project, RoundStatus::InReview);
    $other = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($other)->get(route('client.proofmark.show', $this->project))->assertNotFound();
});

test('a client cannot send rounds', function () {
    $round = roundWithDesign($this->project);

    $this->actingAs($this->client)->post(route('admin.rounds.send', $round))->assertForbidden();
});

test('a round in review waits on the client until it is no longer in review', function () {
    $round = roundWithDesign($this->project);

    expect(waitingTitles($this->client))->not->toContain('Review design round v1');

    $this->actingAs($this->admin)->post(route('admin.rounds.send', $round));

    $item = collect(app(WaitingOnClient::class)->for($this->client))->firstWhere('module', 'Proofmark');

    expect($item->title)->toBe('Review design round v1')
        ->and($item->url)->toBe(route('client.proofmark.show', $this->project))
        ->and($item->dueOn?->toDateString())->toBe($this->project->target_launch_on->toDateString());

    $round->forceFill(['status' => RoundStatus::Approved])->save();
    expect(waitingTitles($this->client))->not->toContain('Review design round v1');
});

test('archived and launched projects do not wait on the client', function () {
    roundWithDesign($this->project, RoundStatus::InReview);

    $this->project->forceFill(['archived_at' => now()])->save();
    expect(waitingTitles($this->client))->toBe([]);

    $this->project->forceFill(['archived_at' => null, 'phase' => ProjectPhase::Launched])->save();
    expect(waitingTitles($this->client))->toBe([]);
});

test('another organization does not see the waiting item', function () {
    roundWithDesign($this->project, RoundStatus::InReview);
    $other = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    expect(waitingTitles($other))->toBe([]);
});
