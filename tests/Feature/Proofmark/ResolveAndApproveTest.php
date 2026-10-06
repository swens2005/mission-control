<?php

use App\Enums\ProjectPhase;
use App\Enums\RoundStatus;
use App\Models\ActivityEntry;
use App\Models\Comment;
use App\Models\Design;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Sam Visser']);
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create(['name' => 'Wedding cakes']);
    $this->client = User::factory()->client($this->organization)->create(['name' => 'Anna de Vries']);
    $this->round = ReviewRound::factory()->for($this->project)->inReview()->create();
    $this->design = Design::factory()->for($this->round, 'round')->create(['title' => 'Home, desktop']);
    Comment::factory()->for($this->design)->create();
    $this->comment = Comment::factory()->for($this->design)->create();
});

test('the studio resolves and reopens a comment', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.comments.resolve', $this->comment))
        ->assertRedirect(route('admin.proofmark.show', [$this->project, 'round' => 1]));

    $this->comment->refresh();
    expect($this->comment->isResolved())->toBeTrue()
        ->and($this->comment->resolved_by_name)->toBe('Sam Visser')
        ->and(ActivityEntry::where('event', 'proofmark.comment_resolved')->sole()->description())
        ->toBe('resolved comment 2 on Home, desktop of Wedding cakes');

    $this->get(route('admin.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('round.designs.0.comments.1.resolved', true)
            ->where('round.designs.0.comments.1.resolvedByName', 'Sam Visser'));

    $this->delete(route('admin.comments.reopen', $this->comment))->assertRedirect();

    expect($this->comment->refresh()->isResolved())->toBeFalse()
        ->and($this->comment->resolved_by_name)->toBeNull()
        ->and(ActivityEntry::where('event', 'proofmark.comment_reopened')->sole()->visible_to_client)->toBeTrue();
});

test('resolving twice records it once', function () {
    $this->actingAs($this->admin);
    $this->put(route('admin.comments.resolve', $this->comment));
    $this->put(route('admin.comments.resolve', $this->comment));

    expect(ActivityEntry::where('event', 'proofmark.comment_resolved')->count())->toBe(1);
});

test('a client cannot resolve comments', function () {
    $this->actingAs($this->client)->put(route('admin.comments.resolve', $this->comment))->assertForbidden();

    expect($this->comment->refresh()->isResolved())->toBeFalse();
});

test('another workspace gets 404 when resolving', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.comments.resolve', $this->comment))
        ->assertNotFound();
});

test('the client approves the round in review', function () {
    $this->actingAs($this->client)
        ->post(route('client.rounds.approve', $this->round), [], ['REMOTE_ADDR' => '203.0.113.9'])
        ->assertRedirect(route('client.proofmark.show', [$this->project, 'round' => 1]));

    $this->round->refresh();
    $entry = ActivityEntry::where('event', 'proofmark.round_approved')->sole();

    expect($this->round->status)->toBe(RoundStatus::Approved)
        ->and($this->round->approved_by_id)->toBe($this->client->id)
        ->and($this->round->approved_by_name)->toBe('Anna de Vries')
        ->and($this->round->approved_ip)->toBe('203.0.113.9')
        ->and($this->round->approved_at)->not->toBeNull()
        ->and($entry->visible_to_client)->toBeTrue()
        ->and($entry->description())->toBe('approved design round v1 of Wedding cakes')
        ->and(Comment::whereNull('resolved_at')->count())->toBe(2);

    $this->get(route('client.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('round.statusLabel', 'Approved')
            ->where('round.approvedByName', 'Anna de Vries')
            ->where('round.canComment', false));
});

test('approving clears the waiting item', function () {
    $titles = fn () => array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($this->client));

    expect($titles())->toContain('Review design round v1');

    $this->actingAs($this->client)->post(route('client.rounds.approve', $this->round));

    expect($titles())->not->toContain('Review design round v1');
});

test('a round can be approved only once, and only while in review', function () {
    $this->actingAs($this->client);
    $this->post(route('client.rounds.approve', $this->round))->assertRedirect();
    $this->post(route('client.rounds.approve', $this->round))->assertForbidden();

    $superseded = ReviewRound::factory()->for($this->project)->create(['status' => RoundStatus::Superseded]);
    $this->post(route('client.rounds.approve', $superseded))->assertForbidden();

    expect(ActivityEntry::where('event', 'proofmark.round_approved')->count())->toBe(1);
});

test('only a client of the organization can approve', function () {
    $other = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();
    $draft = ReviewRound::factory()->for($this->project)->create();

    $this->actingAs($other)->post(route('client.rounds.approve', $this->round))->assertNotFound();
    $this->actingAs($this->client)->post(route('client.rounds.approve', $draft))->assertNotFound();
    $this->actingAs($this->admin)->post(route('client.rounds.approve', $this->round))->assertForbidden();

    expect($this->round->refresh()->status)->toBe(RoundStatus::InReview);
});

test('an approved round is locked', function () {
    $this->actingAs($this->client)->post(route('client.rounds.approve', $this->round));

    $this->post(route('comments.store', $this->design), ['x' => 1, 'y' => 1, 'body' => 'Late'])->assertForbidden();
    $this->actingAs($this->admin)->put(route('admin.comments.resolve', $this->comment))->assertForbidden();
});

test('the project moves to Launch Control only after approval', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('canMoveToLaunch', false));

    $this->post(route('admin.proofmark.to-launch', $this->project))
        ->assertRedirect()
        ->assertSessionHasErrors('phase');
    expect($this->project->refresh()->phase)->toBe(ProjectPhase::Proofmark);

    $this->round->forceFill(['status' => RoundStatus::Approved])->save();

    $this->get(route('admin.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('canMoveToLaunch', true));

    $this->post(route('admin.proofmark.to-launch', $this->project))
        ->assertRedirect(route('admin.launch.show', $this->project));

    expect($this->project->refresh()->phase)->toBe(ProjectPhase::Launch)
        ->and(ActivityEntry::where('event', 'project.phase_changed')->sole()->description())
        ->toBe('moved Wedding cakes from Proofmark to Launch Control');

    // Already moved on: no second move.
    $this->post(route('admin.proofmark.to-launch', $this->project))->assertSessionHasErrors('phase');
});
