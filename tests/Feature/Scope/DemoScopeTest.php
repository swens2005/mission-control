<?php

use App\Models\Intake;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\Proposal;
use App\Support\Money\ProposalTotals;
use App\Support\Sandbox\SandboxFactory;
use App\Support\Waiting\WaitingOnClient;
use Inertia\Testing\AssertableInertia as Assert;

test('a fresh sandbox has the Scope demo: library, intake, v1 with changes and v2 ready', function () {
    $sandbox = app(SandboxFactory::class)->create();

    $this->actingAs($sandbox->admin);

    $project = Project::where('name', 'Seasonal menu pages')->sole();
    [$v2, $v1] = $project->proposals()->with('lines')->get()->all();

    expect(LineItem::count())->toBe(8)
        ->and(Intake::sole()->isSubmitted())->toBeTrue()
        ->and(Intake::sole()->submitted_by_name)->toBe('Anna de Vries')
        ->and($v1->isSent())->toBeTrue()
        ->and($v1->changes_note)->toContain('Easter')
        ->and($v2->isSent())->toBeFalse()
        ->and($v2->lines->pluck('name'))->toContain('Easter pages');

    // Stored v1 totals agree with the maths: €1,530 − 10% = €1,377 + VAT €289.17.
    expect($v1->total_cents)->toBe(166617)
        ->and($v1->total_cents)->toBe(ProposalTotals::calculate(
            $v1->lines->map(fn ($l) => ['unitCents' => $l->unit_price_cents, 'quantity' => $l->quantity])->values()->all(),
            $v1->discount(),
        )->total)
        // v2: €2,665 − 10% = €2,398.50 + VAT €503.69 (503.685, rounded up once).
        ->and($v2->totals()->total)->toBe(290219);

    $this->get(route('admin.scope.show', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('proposal.number', 2)
            ->where('proposal.status', 'draft')
            ->where('changesRequest.version', 1)
            ->where('proposal.totals.total', '€2,902.19'));

    // The full-story tour sends v2 and Anna accepts it.
    $this->post(route('admin.proposals.send', $v2))->assertSessionHasNoErrors();

    $titles = array_map(fn ($item) => $item->title, app(WaitingOnClient::class)->for($sandbox->client));
    expect($titles)->toContain('Accept proposal v2');

    $this->actingAs($sandbox->client)
        ->post(route('client.proposals.accept', $v2), ['signature' => 'Anna de Vries', 'agree' => '1'])
        ->assertRedirect();

    expect($v2->refresh()->isAccepted())->toBeTrue();
});

test('sandbox:prune also removes accepted proposals', function () {
    $sandbox = app(SandboxFactory::class)->create();
    $v2 = Proposal::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->where('number', 2)->sole();
    $v2->load('lines', 'project')->freeze('Sam Visser');
    $v2->forceFill(['accepted_at' => now(), 'accepted_signature' => 'Anna'])->save();

    $sandbox->workspace->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->artisan('sandbox:prune')->assertSuccessful();

    expect(Proposal::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->exists())->toBeFalse()
        ->and(LineItem::withoutGlobalScopes()->where('workspace_id', $sandbox->workspace->id)->exists())->toBeFalse();
});
