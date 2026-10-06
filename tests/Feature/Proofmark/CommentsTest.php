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
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Sam Visser']);
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create(['name' => 'Wedding cakes']);
    $this->client = User::factory()->client($this->organization)->create(['name' => 'Anna de Vries']);
    $this->round = ReviewRound::factory()->for($this->project)->inReview()->create();
    $this->design = Design::factory()->for($this->round, 'round')->create(['title' => 'Home, desktop']);
});

function pin(Design $design, array $data = []): TestResponse
{
    return test()->post(route('comments.store', $design), [
        'x' => 4025,
        'y' => 2500,
        'body' => 'Can the logo be bigger?',
        ...$data,
    ]);
}

test('a client pins a comment with its position', function () {
    $this->actingAs($this->client);

    pin($this->design)->assertRedirect(route('client.proofmark.show', [$this->project, 'round' => 1]));

    $comment = Comment::sole();
    $entry = ActivityEntry::where('event', 'proofmark.commented')->sole();

    expect($comment)
        ->x->toBe(4025)
        ->y->toBe(2500)
        ->body->toBe('Can the logo be bigger?')
        ->author_name->toBe('Anna de Vries')
        ->author_role->toBe('client')
        ->workspace_id->toBe($this->admin->workspace_id)
        ->and($entry->visible_to_client)->toBeTrue()
        ->and($entry->description())->toBe('commented on Home, desktop in round v1 of Wedding cakes');
});

test('the studio can pin comments too', function () {
    $this->actingAs($this->admin);

    pin($this->design)->assertRedirect(route('admin.proofmark.show', [$this->project, 'round' => 1]));

    expect(Comment::sole()->author_role)->toBe('studio');
});

test('positions at the very edges are allowed', function () {
    $this->actingAs($this->client);

    pin($this->design, ['x' => 0, 'y' => 10_000])->assertRedirect(route('client.proofmark.show', [$this->project, 'round' => 1]));

    expect(Comment::sole())->x->toBe(0)->y->toBe(10_000);
});

test('bad positions and empty or long text are refused', function (array $data, string $field) {
    $this->actingAs($this->client);

    pin($this->design, $data)->assertRedirect()->assertSessionHasErrorsIn('comment', $field);

    expect(Comment::count())->toBe(0);
})->with([
    'x below 0' => [['x' => -1], 'x'],
    'y above 100%' => [['y' => 10_001], 'y'],
    'x as a float' => [['x' => 40.5], 'x'],
    'missing y' => [['y' => null], 'y'],
    'empty text' => [['body' => ''], 'body'],
    'too long' => [['body' => str_repeat('a', 2001)], 'body'],
]);

test('comments are refused on rounds that are not in review', function (RoundStatus $status) {
    $this->round->forceFill(['status' => $status])->save();

    $this->actingAs($this->admin);
    pin($this->design)->assertForbidden();

    expect(Comment::count())->toBe(0);
})->with([RoundStatus::Superseded, RoundStatus::Approved]);

test('a client gets 404 on a draft, and on another organization', function () {
    $draft = ReviewRound::factory()->for($this->project)->create();
    $draftDesign = Design::factory()->for($draft, 'round')->create();
    $other = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($this->client);
    pin($draftDesign)->assertNotFound();

    $this->actingAs($other);
    pin($this->design)->assertNotFound();
});

test('another workspace gets 404', function () {
    $this->actingAs(User::factory()->admin()->create());

    pin($this->design)->assertNotFound();
});

test('guests are sent to the login page', function () {
    pin($this->design)->assertRedirect(route('login'));
});

test('comment text is stored as typed, not as HTML', function () {
    $this->actingAs($this->client);
    pin($this->design, ['body' => '<img src=x onerror=alert(1)> Move this']);

    $this->get(route('client.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('round.designs.0.comments.0.body', '<img src=x onerror=alert(1)> Move this'));
});

test('both portals list the comments numbered per design', function () {
    Comment::factory()->for($this->design)->create(['author_name' => 'Anna de Vries', 'body' => 'First']);
    Comment::factory()->for($this->design)->create(['author_name' => 'Sam Visser', 'author_role' => 'studio', 'body' => 'Second']);

    $this->actingAs($this->client)
        ->get(route('client.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('round.canComment', true)
            ->where('round.designs.0.comments.0.number', 1)
            ->where('round.designs.0.comments.1.number', 2)
            ->where('round.designs.0.comments.1.authorRole', 'studio')
            ->where('round.designs.0.comments.1.resolved', false));

    $this->actingAs($this->admin)
        ->get(route('admin.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->has('round.designs.0.comments', 2));
});

test('comments are rate limited', function () {
    $this->actingAs($this->client);

    foreach (range(1, 30) as $i) {
        pin($this->design);
    }

    pin($this->design)->assertTooManyRequests();
});
