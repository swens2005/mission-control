<?php

use App\Enums\DiscountType;
use App\Enums\LineItemKind;
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
    $this->project = Project::factory()->for($this->organization)->create(['name' => 'Seasonal menu pages']);
    $this->client = User::factory()->client($this->organization)->create();
    $this->proposal = Proposal::factory()->for($this->project)->create(['title' => 'Seasonal pages']);
    // 2 × €450 + 1.25 h × €85.50 (€106.88) = €1,006.88; VAT €211.44; total €1,218.32.
    ProposalLine::factory()->for($this->proposal)->create(['name' => 'Seasonal page', 'unit_price_cents' => 45000, 'quantity' => 200, 'position' => 0]);
    ProposalLine::factory()->for($this->proposal)->create(['name' => 'Content work', 'kind' => LineItemKind::Hourly, 'unit_price_cents' => 8550, 'quantity' => 125, 'position' => 1]);
});

function proposalWaitingTitles(User $client): array
{
    return array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($client));
}

function sendProposal(object $test, Proposal $proposal): void
{
    $test->actingAs($test->admin)
        ->post(route('admin.proposals.send', $proposal))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.scope.show', $test->project));
}

test('sending freezes the version and its totals', function () {
    sendProposal($this, $this->proposal);

    $this->proposal->refresh();

    expect($this->proposal->isSent())->toBeTrue()
        ->and($this->proposal->sent_by_name)->toBe('Sam Visser')
        ->and($this->proposal->subtotal_cents)->toBe(100688)
        ->and($this->proposal->vat_cents)->toBe(21144)
        ->and($this->proposal->total_cents)->toBe(121832)
        ->and($this->proposal->lines()->pluck('total_cents')->all())->toBe([90000, 10688])
        ->and(ActivityEntry::where('event', 'scope.proposal_sent')->sole())
        ->visible_to_client->toBeTrue()
        ->description()->toContain('sent proposal v1 of Seasonal menu pages (€1,218.32 incl. VAT)');

    // The stored totals are what's shown, even if the lines were changed
    // behind the app's back.
    ProposalLine::query()->update(['unit_price_cents' => 1]);

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.status', 'sent')
            ->where('proposal.totals.total', '€1,218.32')
            ->where('proposal.lines.0.total', '€900.00')
            ->where('library', []));
});

test('a sent version refuses every change, also by crafted requests', function () {
    sendProposal($this, $this->proposal);
    $line = $this->proposal->lines()->first();

    $this->patch(route('admin.proposals.update', $this->proposal), ['title' => 'Changed'])->assertForbidden();
    $this->put(route('admin.proposals.discount', $this->proposal), ['discount_type' => 'percentage', 'discount_value' => '50'])->assertForbidden();
    $this->post(route('admin.proposal-lines.store', $this->proposal), ['name' => 'x', 'kind' => 'fixed', 'unit_price' => '1', 'quantity' => '1'])->assertForbidden();
    $this->patch(route('admin.proposal-lines.update', $line), ['name' => 'x', 'unit_price' => '1', 'quantity' => '1'])->assertForbidden();
    $this->post(route('admin.proposal-lines.move', $line), ['direction' => 'down'])->assertForbidden();
    $this->delete(route('admin.proposal-lines.destroy', $line))->assertForbidden();
    $this->post(route('admin.proposals.send', $this->proposal))->assertForbidden();

    expect($this->proposal->refresh()->title)->toBe('Seasonal pages')
        ->and($this->proposal->lines()->count())->toBe(2);
});

test('an empty draft or one with a capped discount cannot be sent', function () {
    $empty = Proposal::factory()->for(Project::factory()->for($this->organization))->create();

    $this->actingAs($this->admin)->post(route('admin.proposals.send', $empty))->assertSessionHasErrors('send');

    $this->proposal->forceFill(['discount_type' => DiscountType::Fixed, 'discount_value' => 200000])->save();
    $this->post(route('admin.proposals.send', $this->proposal))->assertSessionHasErrors('send');

    expect($empty->refresh()->isSent())->toBeFalse()
        ->and($this->proposal->refresh()->isSent())->toBeFalse();
});

test('"make changes" copies the sent version into a v2 draft', function () {
    $this->proposal->forceFill(['discount_type' => DiscountType::Percentage, 'discount_value' => 1000, 'discount_label' => 'Returning client'])->save();
    sendProposal($this, $this->proposal);

    $this->post(route('admin.proposals.revise', $this->proposal))->assertRedirect(route('admin.scope.show', $this->project));

    $v2 = $this->project->proposals()->first();

    expect($v2->number)->toBe(2)
        ->and($v2->isSent())->toBeFalse()
        ->and($v2->discount_label)->toBe('Returning client')
        ->and($v2->lines()->pluck('name')->all())->toBe(['Seasonal page', 'Content work'])
        ->and($v2->lines()->whereNotNull('total_cents')->count())->toBe(0);

    // Only one draft at a time, and only from the latest version.
    $this->post(route('admin.proposals.revise', $this->proposal))->assertForbidden();
    $this->post(route('admin.proposals.revise', $v2))->assertForbidden();

    // Editing v2 leaves v1 untouched.
    $this->post(route('admin.proposal-lines.store', $v2), ['name' => 'Easter pages', 'kind' => 'fixed', 'unit_price' => '450', 'quantity' => '2'])->assertSessionHasNoErrors();

    expect($this->proposal->lines()->count())->toBe(2)
        ->and($this->proposal->refresh()->total_cents)->toBe(109649);

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.number', 2)
            ->where('proposal.status', 'draft')
            ->has('versions', 2)
            ->where('versions.1.statusLabel', 'Sent'));

    sendProposal($this, $v2);

    $this->get(route('admin.proposals.show', $this->proposal))
        ->assertInertia(fn (Assert $page) => $page->component('admin/scope/version')
            ->where('proposal.statusLabel', 'Superseded by v2'));
});

test('the client sees only sent versions', function () {
    $this->actingAs($this->client)->get(route('client.scope.show', $this->project))->assertNotFound();
    $this->get(route('client.proposals.show', $this->proposal))->assertNotFound();
    expect(proposalWaitingTitles($this->client))->toBe([]);

    sendProposal($this, $this->proposal);
    $this->post(route('admin.proposals.revise', $this->proposal));
    $v2 = $this->project->proposals()->first();

    // v2 is a draft: the client still sees v1.
    $this->actingAs($this->client)
        ->get(route('client.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('client/scope/show')
            ->where('intake', null)
            ->where('proposal.number', 1)
            ->where('proposal.totals.total', '€1,218.32')
            ->has('versions', 1));
    $this->get(route('client.proposals.show', $v2))->assertNotFound();
    $this->get(route('client.projects.show', $this->project))->assertInertia(fn (Assert $page) => $page->where('hasScope', true));
    expect(proposalWaitingTitles($this->client))->toBe(['Accept proposal v1']);

    sendProposal($this, $v2);

    $this->actingAs($this->client)
        ->get(route('client.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.number', 2)
            ->has('versions', 2)
            ->where('versions.1.statusLabel', 'Superseded by v2'));
    $this->get(route('client.proposals.show', $this->proposal))
        ->assertInertia(fn (Assert $page) => $page->component('client/scope/version')->where('proposal.number', 1));
    expect(proposalWaitingTitles($this->client))->toBe(['Accept proposal v2']);
});

test('other organizations and workspaces get a 404, clients a 403 on studio actions', function () {
    sendProposal($this, $this->proposal);

    $neighbour = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();
    $this->actingAs($neighbour)->get(route('client.proposals.show', $this->proposal))->assertNotFound();
    expect(proposalWaitingTitles($neighbour))->toBe([]);

    $this->actingAs($this->client)->post(route('admin.proposals.revise', $this->proposal))->assertForbidden();
    $this->get(route('admin.proposals.show', $this->proposal))->assertForbidden();

    $stranger = User::factory()->admin()->create();
    $this->actingAs($stranger)->get(route('admin.proposals.show', $this->proposal))->assertNotFound();
    $this->post(route('admin.proposals.revise', $this->proposal))->assertNotFound();
    $this->post(route('admin.proposals.send', $this->proposal))->assertNotFound();
});
