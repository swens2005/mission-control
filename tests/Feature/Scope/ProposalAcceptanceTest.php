<?php

use App\Enums\ProjectPhase;
use App\Models\ActivityEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Models\User;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Sam Visser']);
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Scope)->create(['name' => 'Seasonal menu pages']);
    $this->client = User::factory()->client($this->organization)->create(['name' => 'Anna de Vries']);
    $this->proposal = Proposal::factory()->for($this->project)->create();
    ProposalLine::factory()->for($this->proposal)->create(['unit_price_cents' => 100000]);

    $this->actingAs($this->admin)->post(route('admin.proposals.send', $this->proposal))->assertSessionHasNoErrors();
    $this->proposal->refresh();
});

function acceptanceWaitingTitles(User $client): array
{
    return array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($client));
}

test('the client accepts with a typed name and the tick box', function () {
    expect(acceptanceWaitingTitles($this->client))->toBe(['Accept proposal v1']);

    $this->actingAs($this->client)
        ->get(route('client.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('canRespond', true));

    $this->post(route('client.proposals.accept', $this->proposal), ['signature' => '  Anna de Vries ', 'agree' => '1'], ['REMOTE_ADDR' => '203.0.113.9'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('client.scope.show', $this->project));

    $this->proposal->refresh();

    expect($this->proposal->isAccepted())->toBeTrue()
        ->and($this->proposal->accepted_by_id)->toBe($this->client->id)
        ->and($this->proposal->accepted_by_name)->toBe('Anna de Vries')
        ->and($this->proposal->accepted_signature)->toBe('Anna de Vries')
        ->and($this->proposal->accepted_ip)->toBe('203.0.113.9')
        ->and(acceptanceWaitingTitles($this->client))->toBe([])
        ->and(ActivityEntry::where('event', 'scope.proposal_accepted')->sole())
        ->visible_to_client->toBeTrue()
        ->description()->toContain('accepted proposal v1 of Seasonal menu pages (€1,210.00 incl. VAT)');

    $this->get(route('client.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canRespond', false)
            ->where('proposal.status', 'accepted')
            ->where('proposal.acceptance.signature', 'Anna de Vries')
            ->where('proposal.statusLabel', 'Accepted by Anna de Vries, '.now()->format('j M Y')));
});

test('accepting needs the name and the tick box', function (array $input, string $field) {
    $this->actingAs($this->client)
        ->post(route('client.proposals.accept', $this->proposal), $input)
        ->assertSessionHasErrors($field);

    expect($this->proposal->refresh()->isAccepted())->toBeFalse();
})->with([
    'no name' => [['agree' => '1'], 'signature'],
    'blank name' => [['signature' => '   ', 'agree' => '1'], 'signature'],
    'no tick' => [['signature' => 'Anna de Vries'], 'agree'],
    'long name' => [['signature' => str_repeat('a', 121), 'agree' => '1'], 'signature'],
]);

test('an accepted version is immutable and gets no new versions', function () {
    $this->actingAs($this->client)->post(route('client.proposals.accept', $this->proposal), ['signature' => 'Anna', 'agree' => '1']);

    // Once only, and nothing else afterwards.
    $this->post(route('client.proposals.accept', $this->proposal), ['signature' => 'Anna', 'agree' => '1'])->assertForbidden();
    $this->post(route('client.proposals.changes', $this->proposal), ['note' => 'Actually…'])->assertForbidden();

    $this->actingAs($this->admin)->post(route('admin.proposals.revise', $this->proposal))->assertForbidden();
    expect(Proposal::count())->toBe(1);

    // The model refuses changes even past the policies.
    expect(fn () => $this->proposal->refresh()->forceFill(['title' => 'Changed'])->save())->toThrow(LogicException::class)
        ->and(fn () => $this->proposal->delete())->toThrow(LogicException::class)
        ->and(fn () => $this->proposal->lines()->first()->forceFill(['quantity' => 1])->save())->toThrow(LogicException::class);
});

test('a draft started before the client accepted can no longer be sent', function () {
    $this->post(route('admin.proposals.revise', $this->proposal))->assertRedirect();
    $draft = $this->project->proposals()->first();

    $this->actingAs($this->client)->post(route('client.proposals.accept', $this->proposal), ['signature' => 'Anna', 'agree' => '1'])->assertRedirect();

    $this->actingAs($this->admin)->post(route('admin.proposals.send', $draft))->assertForbidden();

    // The studio's page shows the accepted version, not the stale draft.
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.number', 1)
            ->where('proposal.status', 'accepted')
            ->where('canMoveToPalette', true));
});

test('the client asks for changes with a note, and the studio sees it', function () {
    $this->actingAs($this->client)
        ->post(route('client.proposals.changes', $this->proposal), ['note' => ''])
        ->assertSessionHasErrors('note');

    $this->post(route('client.proposals.changes', $this->proposal), ['note' => 'Could you add the Easter pages too?'])
        ->assertRedirect(route('client.scope.show', $this->project));

    expect($this->proposal->refresh()->changes_note)->toBe('Could you add the Easter pages too?')
        ->and(acceptanceWaitingTitles($this->client))->toBe([])
        ->and(ActivityEntry::where('event', 'scope.changes_requested')->sole()->visible_to_client)->toBeTrue();

    // Answered: no accepting this version afterwards.
    $this->post(route('client.proposals.accept', $this->proposal), ['signature' => 'Anna', 'agree' => '1'])->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.status', 'changes')
            ->where('changesRequest.note', 'Could you add the Easter pages too?')
            ->where('changesRequest.byName', 'Anna de Vries'));

    // The studio makes v2 and sends it; the client is asked again.
    $this->post(route('admin.proposals.revise', $this->proposal))->assertRedirect();
    $v2 = $this->project->proposals()->first();
    $this->post(route('admin.proposals.send', $v2))->assertSessionHasNoErrors();

    expect(acceptanceWaitingTitles($this->client))->toBe(['Accept proposal v2']);

    // Only the latest sent version can be answered.
    $this->actingAs($this->client)->post(route('client.proposals.accept', $this->proposal), ['signature' => 'Anna', 'agree' => '1'])->assertForbidden();
    $this->post(route('client.proposals.accept', $v2), ['signature' => 'Anna', 'agree' => '1'])->assertRedirect();
});

test('only the organization\'s client answers; the studio cannot', function () {
    $neighbour = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($neighbour)->post(route('client.proposals.accept', $this->proposal), ['signature' => 'X', 'agree' => '1'])->assertNotFound();
    $this->post(route('client.proposals.changes', $this->proposal), ['note' => 'X'])->assertNotFound();

    $this->actingAs($this->admin)->post(route('client.proposals.accept', $this->proposal), ['signature' => 'X', 'agree' => '1'])->assertForbidden();

    $draft = Proposal::factory()->for(Project::factory()->for($this->organization))->create();
    $this->actingAs($this->client)->post(route('client.proposals.accept', $draft), ['signature' => 'X', 'agree' => '1'])->assertNotFound();

    expect(Proposal::whereNotNull('accepted_at')->count())->toBe(0);
});

test('the project moves to Palette Lab only after acceptance', function () {
    $this->post(route('admin.scope.to-palette', $this->project))->assertSessionHasErrors('phase');
    expect($this->project->refresh()->phase)->toBe(ProjectPhase::Scope);

    $this->actingAs($this->client)->post(route('client.proposals.accept', $this->proposal), ['signature' => 'Anna', 'agree' => '1']);

    $this->actingAs($this->admin)
        ->post(route('admin.scope.to-palette', $this->project))
        ->assertRedirect(route('admin.palette.show', $this->project));

    expect($this->project->refresh()->phase)->toBe(ProjectPhase::Palette)
        ->and(ActivityEntry::where('event', 'project.phase_changed')->latest('id')->first()->description())->toContain('from Scope to Palette Lab');

    // Once moved, there's no second move.
    $this->post(route('admin.scope.to-palette', $this->project))->assertSessionHasErrors('phase');
});
