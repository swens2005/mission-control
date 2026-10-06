<?php

use App\Enums\RoundStatus;
use App\Models\Comment;
use App\Models\Design;
use App\Models\Project;
use App\Models\ReviewRound;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Proofmark\ImageProcessor;
use App\Support\Sandbox\DemoTemplate;
use App\Support\Sandbox\SandboxFactory;
use App\Support\Waiting\WaitingOnClient;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('a fresh sandbox has the Wedding cakes review waiting for the demo client', function () {
    $sandbox = (new SandboxFactory)->create();
    $project = Project::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('name', 'Wedding cakes')->sole();

    $rounds = ReviewRound::withoutGlobalScopes()->where('project_id', $project->id)->orderBy('number')->get();

    expect($project->organization_id)->toBe($sandbox->client->organization_id)
        ->and($rounds->pluck('status')->all())->toBe([RoundStatus::Superseded, RoundStatus::InReview])
        ->and($rounds[1]->sent_at)->not->toBeNull();

    $designs = Design::withoutGlobalScopes()->where('review_round_id', $rounds[1]->id)->get();
    $comments = Comment::withoutGlobalScopes()->whereIn('design_id', $designs->pluck('id'))->get();
    $oldComments = Comment::withoutGlobalScopes()
        ->whereIn('design_id', Design::withoutGlobalScopes()->where('review_round_id', $rounds[0]->id)->pluck('id'))
        ->get();

    expect($designs)->toHaveCount(4)
        ->and($designs->pluck('source')->unique()->all())->toBe(['demo'])
        ->and($comments->where('author_role', 'client'))->toHaveCount(2)
        ->and($comments->where('author_role', 'studio'))->toHaveCount(1)
        ->and($comments->whereNotNull('resolved_at'))->toHaveCount(0)
        ->and($oldComments)->toHaveCount(3)
        ->and($oldComments->whereNull('resolved_at'))->toHaveCount(0);

    $titles = array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($sandbox->client));
    expect($titles)->toContain('Review design round v2');
});

test('the demo client sees the round and its images', function () {
    $sandbox = (new SandboxFactory)->create();
    $project = Project::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('name', 'Wedding cakes')->sole();

    $this->actingAs($sandbox->client)
        ->get(route('client.proofmark.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('round.label', 'v2')
            ->where('round.canComment', true)
            ->has('round.designs', 4)
            ->has('round.designs.0.comments', 2));

    $design = Design::withoutGlobalScopes()->where('path', 'wedding-home-desktop-v2.webp')->where('workspace_id', $sandbox->workspace->id)->sole();

    $this->get(route('designs.image', $design))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/webp');
});

test('Repair booking has a draft round for the studio only', function () {
    $sandbox = (new SandboxFactory)->create();
    $project = Project::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('name', 'Repair booking')->sole();

    expect(ReviewRound::withoutGlobalScopes()->where('project_id', $project->id)->sole()->status)->toBe(RoundStatus::Draft);

    $this->actingAs($sandbox->admin)
        ->get(route('admin.proofmark.show', $project))
        ->assertInertia(fn (Assert $page) => $page->where('round.statusLabel', 'Draft')->has('round.designs', 1));
});

test('creating and pruning sandboxes never writes or deletes demo files', function () {
    $sandbox = (new SandboxFactory)->create();

    Storage::disk('local')->assertDirectoryEmpty('/');

    // Removing a demo design from a draft leaves the shipped file alone.
    $draftDesign = Design::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('source', 'demo')
        ->whereHas('round', fn ($q) => $q->withoutGlobalScopes()->where('status', RoundStatus::Draft))->sole();
    $this->actingAs($sandbox->admin)->delete(route('admin.designs.destroy', $draftDesign))->assertRedirect();

    $sandbox->workspace->update(['expires_at' => now()->subMinute()]);
    $this->artisan('sandbox:prune')->assertSuccessful();

    expect(Workspace::find($sandbox->workspace->id))->toBeNull()
        ->and(file_exists(resource_path('demo/proofmark/repair-booking-desktop.webp')))->toBeTrue();
});

test('demo designs do not count towards the upload quota', function () {
    $sandbox = (new SandboxFactory)->create();

    $this->actingAs($sandbox->admin)
        ->get(route('admin.proofmark.show', Project::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('name', 'Wedding cakes')->sole()))
        ->assertInertia(fn (Assert $page) => $page->where('quota.used', '0 KB'));
});

test('every shipped demo image passes the same checks as an upload', function (string $file) {
    $image = (new ImageProcessor)->process(resource_path('demo/proofmark/'.$file));

    expect($image->mime)->toBe('image/webp');
})->with(function () {
    return collect(DemoTemplate::proofmark())->flatten(1)
        ->flatMap(fn (array $round) => array_column($round['designs'], 'file'))
        ->unique()->values()->all();
});

test('the shipped demo images stay small', function () {
    $total = collect(glob(resource_path('demo/proofmark/*.webp')) ?: [])->sum(fn (string $file) => filesize($file));

    expect($total)->toBeLessThan(600 * 1024);
});

test('a demo admin cannot see another sandbox\'s Proofmark', function () {
    $mine = (new SandboxFactory)->create();
    $theirs = (new SandboxFactory)->create();
    $theirProject = Project::withoutGlobalScopes()->where('workspace_id', $theirs->workspace->id)->where('name', 'Wedding cakes')->sole();
    $theirDesign = Design::withoutGlobalScopes()->where('workspace_id', $theirs->workspace->id)->first();

    $this->actingAs($mine->admin)->get(route('admin.proofmark.show', $theirProject))->assertNotFound();
    $this->get(route('designs.image', $theirDesign))->assertNotFound();

    expect(User::withoutGlobalScopes()->count())->toBe(4);
});
