<?php

use App\Enums\DiscountType;
use App\Enums\LineItemKind;
use App\Models\ActivityEntry;
use App\Models\LineItem;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->create(['name' => 'Seasonal menu pages']);
    $this->client = User::factory()->client($this->organization)->create();
    $this->design = LineItem::factory()->for($this->admin->workspace)->create(['name' => 'Seasonal page', 'unit_price_cents' => 45000, 'default_quantity' => 200]);
    $this->content = LineItem::factory()->for($this->admin->workspace)->hourly(8550, 150)->create();
});

function startProposal(object $test): Proposal
{
    $test->actingAs($test->admin)
        ->post(route('admin.proposals.store', $test->project))
        ->assertRedirect(route('admin.scope.show', $test->project));

    return $test->project->proposals()->sole();
}

test('the studio starts v1 once', function () {
    $proposal = startProposal($this);
    $this->post(route('admin.proposals.store', $this->project))->assertRedirect();

    expect(Proposal::count())->toBe(1)
        ->and($proposal->number)->toBe(1)
        ->and($proposal->title)->toBe('Proposal: Seasonal menu pages')
        ->and(ActivityEntry::where('event', 'scope.proposal_started')->sole()->visible_to_client)->toBeFalse();

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('admin/scope/show')
            ->where('proposal.number', 1)
            ->has('proposal.lines', 0)
            ->where('proposal.totals.total', '€0.00')
            ->has('library', 2)
            ->where('library.0.label', 'Fixed price')
            ->where('library.0.items.0.price', '€450.00')
            ->where('library.1.items.0.price', '€85.50 per hour'));
});

test('lines come from the library as copies, or as custom lines', function () {
    $proposal = startProposal($this);

    // Usual quantity, then a chosen one.
    $this->post(route('admin.proposal-lines.library', $proposal), ['line_item_id' => $this->design->id])->assertSessionHasNoErrors();
    $this->post(route('admin.proposal-lines.library', $proposal), ['line_item_id' => $this->content->id, 'quantity' => '1,25'])->assertSessionHasNoErrors();
    $this->post(route('admin.proposal-lines.store', $proposal), [
        'name' => 'Photo shoot', 'kind' => 'fixed', 'unit_price' => '350', 'quantity' => '1',
    ])->assertSessionHasNoErrors();

    $lines = $proposal->lines()->get();

    expect($lines->pluck('name')->all())->toBe(['Seasonal page', 'Content work', 'Photo shoot'])
        ->and($lines[0]->quantity)->toBe(200)
        ->and($lines[0]->line_item_id)->toBe($this->design->id)
        ->and($lines[1]->quantity)->toBe(125)
        ->and($lines[2]->line_item_id)->toBeNull();

    // Editing or archiving the library doesn't touch the proposal.
    $this->design->forceFill(['unit_price_cents' => 99900, 'name' => 'Renamed', 'archived_at' => now()])->save();

    // 2 × €450 + 1.25 h × €85.50 (€106.875 → €106.88) + €350 = €1,356.88; VAT €284.94.
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.lines.0.name', 'Seasonal page')
            ->where('proposal.lines.0.total', '€900.00')
            ->where('proposal.lines.1.total', '€106.88')
            ->where('proposal.lines.1.quantity', '1.25')
            ->where('proposal.totals.subtotal', '€1,356.88')
            ->where('proposal.totals.vat', '€284.94')
            ->where('proposal.totals.total', '€1,641.82')
            ->has('library', 1));

    expect($proposal->totals()->total)->toBe(164182)
        ->and(ActivityEntry::where('event', 'scope.line_added')->count())->toBe(3);
});

test('archived and other workspaces\' items cannot be added', function () {
    $proposal = startProposal($this);
    $archived = LineItem::factory()->for($this->admin->workspace)->create(['archived_at' => now()]);
    $foreign = LineItem::factory()->create();

    foreach ([$archived->id, $foreign->id, 999999, null] as $id) {
        $this->post(route('admin.proposal-lines.library', $proposal), ['line_item_id' => $id])
            ->assertSessionHasErrors('line_item_id');
    }

    expect(ProposalLine::count())->toBe(0);
});

test('lines are validated', function (array $input, string $field) {
    $proposal = startProposal($this);

    $this->post(route('admin.proposal-lines.store', $proposal), [
        'name' => 'Extra', 'kind' => 'hourly', 'unit_price' => '85', 'quantity' => '1', ...$input,
    ])->assertSessionHasErrors($field);
})->with([
    'no name' => [['name' => ''], 'name'],
    'bad price' => [['unit_price' => 'lots'], 'unit_price'],
    'price too high' => [['unit_price' => '20000000'], 'unit_price'],
    'zero quantity' => [['quantity' => '0'], 'quantity'],
    'off the quarter' => [['quantity' => '1.1'], 'quantity'],
    'fixed in halves' => [['kind' => 'fixed', 'quantity' => '0.5'], 'quantity'],
    'unknown kind' => [['kind' => 'daily'], 'kind'],
]);

test('lines are edited, reordered and removed', function () {
    $proposal = startProposal($this);
    $first = ProposalLine::factory()->for($proposal)->create(['name' => 'One', 'position' => 0]);
    $second = ProposalLine::factory()->for($proposal)->create(['name' => 'Two', 'position' => 1, 'kind' => LineItemKind::Hourly]);

    $this->patch(route('admin.proposal-lines.update', $second), [
        'name' => 'Two, edited', 'unit_price' => '90', 'quantity' => '2.75',
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.scope.show', $this->project));

    expect($second->refresh()->quantity)->toBe(275)
        ->and($second->unit_price_cents)->toBe(9000);

    $this->post(route('admin.proposal-lines.move', $second), ['direction' => 'up'])->assertRedirect();
    expect($proposal->lines()->pluck('name')->all())->toBe(['Two, edited', 'One']);

    $this->delete(route('admin.proposal-lines.destroy', $first))->assertRedirect(route('admin.scope.show', $this->project));
    expect($proposal->lines()->count())->toBe(1)
        ->and(ActivityEntry::where('event', 'scope.line_removed')->sole()->description())->toContain('removed One from proposal v1');
});

test('the discount is a percentage or a fixed amount, never above the subtotal', function () {
    $proposal = startProposal($this);
    ProposalLine::factory()->for($proposal)->create(['unit_price_cents' => 123455]);

    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'percentage', 'discount_value' => '10', 'discount_label' => 'Returning client'])
        ->assertSessionHasNoErrors();

    // 10% of €1,234.55 = €123.455 → €123.46 (ADR 0010).
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.discount.description', '10%')
            ->where('proposal.discount.label', 'Returning client')
            ->where('proposal.totals.discount', '−€123.46')
            ->where('proposal.totals.net', '€1,111.09')
            ->where('proposal.totals.total', '€1,344.42'));

    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'fixed', 'discount_value' => '1.234,56'])
        ->assertSessionHasErrors(['discount_value' => 'The discount can’t be more than the subtotal (€1,234.55).']);
    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'percentage', 'discount_value' => '101'])
        ->assertSessionHasErrors('discount_value');
    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'fixed'])
        ->assertSessionHasErrors('discount_value');

    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'fixed', 'discount_value' => '234,55'])
        ->assertSessionHasNoErrors();
    expect($proposal->refresh()->discount_type)->toBe(DiscountType::Fixed)
        ->and($proposal->discount_value)->toBe(23455)
        ->and($proposal->discount_label)->toBeNull();

    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'none', 'discount_value' => '5'])
        ->assertSessionHasNoErrors();
    expect($proposal->refresh()->discount_value)->toBe(0)
        ->and(ActivityEntry::where('event', 'scope.discount_changed')->count())->toBe(3);
});

test('removing lines below a fixed discount caps it and warns', function () {
    $proposal = startProposal($this);
    $line = ProposalLine::factory()->for($proposal)->create(['unit_price_cents' => 50000]);
    ProposalLine::factory()->for($proposal)->create(['unit_price_cents' => 10000]);
    $proposal->forceFill(['discount_type' => DiscountType::Fixed, 'discount_value' => 30000])->save();

    $this->delete(route('admin.proposal-lines.destroy', $line))->assertRedirect();

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.discount.capped', true)
            ->where('proposal.totals.discount', '−€100.00')
            ->where('proposal.totals.total', '€0.00'));
});

test('the title and introduction are saved', function () {
    $proposal = startProposal($this);

    $this->patch(route('admin.proposals.update', $proposal), ['title' => '  Seasonal pages  ', 'introduction' => "Hi Anna,\n\nHere it is."])
        ->assertSessionHasNoErrors();

    expect($proposal->refresh()->title)->toBe('Seasonal pages')
        ->and($proposal->introduction)->toBe("Hi Anna,\n\nHere it is.");

    $this->patch(route('admin.proposals.update', $proposal), ['title' => ''])->assertSessionHasErrors('title');
});

test('other workspaces get a 404, clients a 403', function () {
    $proposal = Proposal::factory()->for($this->project)->create();
    $line = ProposalLine::factory()->for($proposal)->create();

    $stranger = User::factory()->admin()->create();
    $this->actingAs($stranger);
    $this->post(route('admin.proposals.store', $this->project))->assertNotFound();
    $this->patch(route('admin.proposals.update', $proposal), ['title' => 'Mine'])->assertNotFound();
    $this->put(route('admin.proposals.discount', $proposal), ['discount_type' => 'none'])->assertNotFound();
    $this->post(route('admin.proposal-lines.store', $proposal), ['name' => 'x', 'kind' => 'fixed', 'unit_price' => '1', 'quantity' => '1'])->assertNotFound();
    $this->patch(route('admin.proposal-lines.update', $line), ['name' => 'x', 'unit_price' => '1', 'quantity' => '1'])->assertNotFound();
    $this->delete(route('admin.proposal-lines.destroy', $line))->assertNotFound();

    $this->actingAs($this->client);
    $this->get(route('admin.scope.show', $this->project))->assertForbidden();
    $this->delete(route('admin.proposal-lines.destroy', $line))->assertForbidden();

    expect(ProposalLine::count())->toBe(1);
});
