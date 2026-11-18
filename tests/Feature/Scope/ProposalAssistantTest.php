<?php

use App\Mail\AiCapReached;
use App\Models\ActivityEntry;
use App\Models\AiDraft;
use App\Models\Intake;
use App\Models\LineItem;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Ai\DraftLimits;
use App\Support\Ai\ProposalAssistant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Story 42: the proposal assistant. Http::fake() stands in for the Claude
 * API: nothing here calls it.
 */

beforeEach(function () {
    config([
        'services.anthropic.key' => 'test-key',
        'services.anthropic.monthly_cap_dollars' => 10,
        'services.anthropic.cap_email' => 'meagan@example.test',
        'services.anthropic.drafts_per_sandbox' => 3,
        'services.anthropic.demo_drafts_per_day' => 50,
    ]);

    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create(['name' => 'Bakkerij Zoet']);
    $this->project = Project::factory()->for($this->organization)->create(['name' => 'Seasonal menu pages']);
    $this->intake = Intake::factory()->submitted()->create([
        'project_id' => $this->project->id,
        'goals' => 'Show the menu for each season and take pre-orders.',
        'pages' => ['Home', 'Spring menu', 'Summer menu'],
    ]);
    $this->seasonal = LineItem::factory()->for($this->admin->workspace)->create(['name' => 'Seasonal page', 'unit_price_cents' => 45000, 'default_quantity' => 100]);
    $this->copy = LineItem::factory()->for($this->admin->workspace)->hourly(8500, 400)->create(['name' => 'Copywriting']);
    $this->archived = LineItem::factory()->for($this->admin->workspace)->create(['name' => 'Old flyer design', 'archived_at' => now()]);
    $this->proposal = Proposal::factory()->for($this->project)->create();
});

/**
 * A Messages API answer with one propose_lines call.
 *
 * @param  list<array<string, mixed>>  $lines
 */
function claudeAnswer(array $lines, string $introduction = 'Thanks for telling us about your seasons.', int $in = 3000, int $out = 1000): array
{
    return [
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-sonnet-5-5',
        'stop_reason' => 'tool_use',
        'content' => [[
            'type' => 'tool_use',
            'id' => 'toolu_test',
            'name' => ProposalAssistant::TOOL,
            'input' => ['introduction' => $introduction, 'lines' => $lines],
        ]],
        'usage' => ['input_tokens' => $in, 'output_tokens' => $out],
    ];
}

function fakeClaude(array $answer, int $status = 200): void
{
    Http::fake([ProposalAssistant::ENDPOINT => Http::response($answer, $status)]);
}

test('the studio sees "Draft with AI" only with a key, a draft version and a submitted intake', function () {
    $this->actingAs($this->admin)->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('ai.refusal', null)->where('ai.remaining', null)->where('ai.draft', null));

    config(['services.anthropic.key' => null]);
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('ai', null));
    $this->post(route('admin.ai-drafts.store', $this->proposal))->assertNotFound();

    config(['services.anthropic.key' => 'test-key']);
    $this->intake->forceFill(['submitted_at' => null])->save();
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('ai', null));
    $this->post(route('admin.ai-drafts.store', $this->proposal))->assertSessionHasErrors('ai');

    Http::assertNothingSent();
});

test('the request carries only the intake answers and the active library', function () {
    User::factory()->client($this->organization)->create(['email' => 'anna@zoet.test']);
    fakeClaude(claudeAnswer([]));

    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal));

    Http::assertSent(function (HttpRequest $request) {
        $body = (string) json_encode($request->data(), JSON_UNESCAPED_UNICODE);

        return $request->url() === ProposalAssistant::ENDPOINT
            && $request->hasHeader('x-api-key', 'test-key')
            && $request->hasHeader('anthropic-version', ProposalAssistant::API_VERSION)
            && $request['model'] === 'claude-sonnet-5-5'
            && $request['tools'][0]['strict'] === true
            && $request['tool_choice'] === ['type' => 'auto']
            && str_contains($body, 'Show the menu for each season')
            && str_contains($body, 'Seasonal page')
            && str_contains($body, '€450.00')
            && ! str_contains($body, 'Old flyer design')
            && ! str_contains($body, 'Bakkerij Zoet')
            && ! str_contains($body, 'Anna de Vries')
            && ! str_contains($body, 'anna@zoet.test');
    });
});

test('a draft waits for review; bad ids and quantities are dropped and nothing is saved yet', function () {
    $other = LineItem::factory()->create(['name' => 'Another studio’s item']);

    fakeClaude(claudeAnswer([
        ['line_item_id' => $this->seasonal->id, 'quantity' => 2, 'reason' => 'Intake: spring and summer menus'],
        ['line_item_id' => $this->copy->id, 'quantity' => 3.5, 'reason' => 'Intake: menu texts'],
        ['line_item_id' => $this->archived->id, 'quantity' => 1, 'reason' => 'archived'],
        ['line_item_id' => $other->id, 'quantity' => 1, 'reason' => 'other workspace'],
        ['line_item_id' => 999999, 'quantity' => 1, 'reason' => 'made up'],
        ['line_item_id' => $this->seasonal->id, 'quantity' => 1, 'reason' => 'twice'],
        ['line_item_id' => $this->copy->id, 'quantity' => 0.3, 'reason' => 'not a quarter'],
    ]));

    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.scope.show', $this->project));

    expect($this->proposal->lines()->count())->toBe(0)
        ->and(AiDraft::sole()->cost_micro_usd)->toBe(3000 * 2 + 1000 * 10)
        ->and(AiDraft::sole()->lines)->toBe(2);

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('ai.draft.lines', 2)
            ->where('ai.draft.lines.0.name', 'Seasonal page')
            ->where('ai.draft.lines.0.quantity', '2')
            ->where('ai.draft.lines.0.price', '€450.00')
            ->where('ai.draft.lines.0.reason', 'Intake: spring and summer menus')
            ->where('ai.draft.lines.1.quantity', '3.5')
            ->where('ai.draft.dropped', 5)
            ->where('ai.draft.introduction', 'Thanks for telling us about your seasons.'));
});

test('a fixed-price item can’t come in halves', function () {
    fakeClaude(claudeAnswer([['line_item_id' => $this->seasonal->id, 'quantity' => 1.5, 'reason' => 'Intake: x']]));

    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal));

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->has('ai.draft.lines', 0)->where('ai.draft.dropped', 1));
});

test('the studio keeps, changes or removes lines; prices come from the library', function () {
    fakeClaude(claudeAnswer([
        ['line_item_id' => $this->seasonal->id, 'quantity' => 2, 'reason' => 'Intake: menus'],
        ['line_item_id' => $this->copy->id, 'quantity' => 4, 'reason' => 'Intake: texts'],
    ]));
    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal));

    $this->post(route('admin.ai-drafts.apply', $this->proposal), [
        'introduction' => 'Hello Bakkerij Zoet, here is our plan.',
        'use_introduction' => true,
        'lines' => [
            ['line_item_id' => $this->seasonal->id, 'keep' => true, 'quantity' => '3', 'unit_price' => '1'],
            ['line_item_id' => $this->copy->id, 'keep' => false, 'quantity' => '4'],
            // Not in the draft: ignored.
            ['line_item_id' => $this->archived->id, 'keep' => true, 'quantity' => '1'],
        ],
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.scope.show', $this->project));

    $line = $this->proposal->lines()->sole();

    expect($line->name)->toBe('Seasonal page')
        ->and($line->unit_price_cents)->toBe(45000)
        ->and($line->quantity)->toBe(300)
        ->and($line->line_item_id)->toBe($this->seasonal->id)
        ->and($this->proposal->fresh()->introduction)->toBe('Hello Bakkerij Zoet, here is our plan.');

    $entry = ActivityEntry::where('event', 'scope.ai_draft_created')->sole();
    expect($entry->visible_to_client)->toBeFalse()
        ->and($entry->properties['lines'])->toBe('1')
        ->and(json_encode($entry->properties))->not->toContain('Thanks for telling');

    // The draft is gone after adding it.
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('ai.draft', null));
    $this->post(route('admin.ai-drafts.apply', $this->proposal), ['lines' => []])->assertSessionHasErrors('ai');
});

test('a changed quantity is checked like a hand-made line', function () {
    fakeClaude(claudeAnswer([['line_item_id' => $this->seasonal->id, 'quantity' => 2, 'reason' => 'Intake: menus']]));
    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal));

    $this->post(route('admin.ai-drafts.apply', $this->proposal), [
        'lines' => [['line_item_id' => $this->seasonal->id, 'keep' => true, 'quantity' => '1.5']],
    ])->assertSessionHasErrors(['lines.0.quantity' => 'Fixed-price items come in whole numbers.']);

    expect($this->proposal->lines()->count())->toBe(0);
});

test('discarding a draft adds nothing', function () {
    fakeClaude(claudeAnswer([['line_item_id' => $this->seasonal->id, 'quantity' => 2, 'reason' => 'Intake: menus']]));
    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal));

    $this->delete(route('admin.ai-drafts.destroy', $this->proposal))->assertRedirect();

    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('ai.draft', null));
    expect($this->proposal->lines()->count())->toBe(0);
});

test('an API error, a timeout or a useless answer gives a plain message', function (Closure $fake, string $message) {
    $fake();

    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal))
        ->assertSessionHasErrors(['ai' => $message]);

    expect($this->proposal->lines()->count())->toBe(0);
})->with([
    'overloaded' => [fn () => fakeClaude(['type' => 'error', 'error' => ['type' => 'overloaded_error']], 529), 'The assistant is busy right now. Try again in a minute.'],
    'bad request' => [fn () => fakeClaude(['type' => 'error', 'error' => ['type' => 'invalid_request_error']], 400), 'The assistant isn’t available right now. Build the proposal by hand, or try again later.'],
    'timeout' => [fn () => Http::fake(fn () => throw new ConnectionException('timed out')), 'The assistant took too long to answer. Try again in a minute.'],
    'refusal' => [fn () => fakeClaude(['stop_reason' => 'refusal', 'content' => [], 'usage' => ['input_tokens' => 10, 'output_tokens' => 0]]), 'The assistant didn’t come back with a usable draft. Try again, or build it by hand.'],
    'no tool call' => [fn () => fakeClaude(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Hi']], 'usage' => ['input_tokens' => 10, 'output_tokens' => 5]]), 'The assistant didn’t come back with a usable draft. Try again, or build it by hand.'],
]);

test('a sandbox gets 3 drafts, and the demo a number per day', function () {
    $this->admin->workspace->update(['is_sandbox' => true]);
    fakeClaude(claudeAnswer([['line_item_id' => $this->seasonal->id, 'quantity' => 1, 'reason' => 'Intake: x']], in: 10, out: 10));

    $this->actingAs($this->admin);

    foreach ([2, 1, 0] as $left) {
        $this->post(route('admin.ai-drafts.store', $this->proposal))->assertSessionHasNoErrors();
        $this->get(route('admin.scope.show', $this->project))
            ->assertInertia(fn (Assert $page) => $page->where('ai.remaining', $left));
    }

    $this->post(route('admin.ai-drafts.store', $this->proposal))
        ->assertSessionHasErrors(['ai' => 'This demo has used its 3 AI drafts. Start a new demo to try again.']);
    Http::assertSentCount(3);

    // Another sandbox, but the demo's daily total is reached.
    config(['services.anthropic.demo_drafts_per_day' => 3]);
    $visitor = User::factory()->admin()->create();
    $visitor->workspace->update(['is_sandbox' => true]);
    $refusal = app(DraftLimits::class)->refusal($visitor->workspace->fresh());

    expect($refusal)->toBe('The demo’s AI drafts for today are used up. Try again tomorrow.');
});

test('the real studio has no per-sandbox limit', function () {
    fakeClaude(claudeAnswer([], in: 10, out: 10));
    $this->actingAs($this->admin);

    foreach (range(1, 4) as $i) {
        $this->post(route('admin.ai-drafts.store', $this->proposal))->assertSessionHasNoErrors();
    }

    Http::assertSentCount(4);
});

test('at the monthly cap drafting stops for everyone and Meagan gets one email', function () {
    Mail::fake();
    config(['services.anthropic.monthly_cap_dollars' => 1]);
    // 400,000 tokens in and 20,000 out: $0.80 + $0.20 = $1.00, the cap.
    fakeClaude(claudeAnswer([], in: 400_000, out: 20_000));

    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal))->assertSessionHasNoErrors();

    expect(AiDraft::sole()->cost_micro_usd)->toBe(1_000_000);
    Mail::assertSent(AiCapReached::class, fn (AiCapReached $mail) => $mail->hasTo('meagan@example.test') && $mail->cap === '$1.00');

    $paused = 'Drafting with AI is paused until next month: this month’s budget is used up.';
    $this->post(route('admin.ai-drafts.store', $this->proposal))->assertSessionHasErrors(['ai' => $paused]);
    $this->get(route('admin.scope.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('ai.refusal', $paused));

    // Even past the cap (a call already under way), one email a month.
    app(DraftLimits::class)->record($this->admin, 1000, 1000, 0);
    Mail::assertSentCount(1);
    Http::assertSentCount(1);

    // A new month starts again.
    $this->travelTo(now()->addMonthNoOverflow()->startOfMonth()->addHour());
    $this->post(route('admin.ai-drafts.store', $this->proposal))->assertSessionHasNoErrors();
});

test('without a cap email address the warning is logged', function () {
    Mail::fake();
    config(['services.anthropic.monthly_cap_dollars' => 0, 'services.anthropic.cap_email' => null]);
    Log::spy();

    app(DraftLimits::class)->record($this->admin, 10, 10, 0);

    Mail::assertNothingSent();
    Log::shouldHaveReceived('warning')->once();
});

test('a pruned sandbox keeps its spend in the month', function () {
    $sandbox = User::factory()->admin()->create();
    $sandbox->workspace->update(['is_sandbox' => true]);
    app(DraftLimits::class)->record($sandbox, 1000, 1000, 1);

    $sandbox->workspace->delete();

    expect(AiDraft::sole()->workspace_id)->toBeNull()
        ->and(app(DraftLimits::class)->monthSpent())->toBe(12_000);
});

test('other studios and clients can’t draft on this proposal', function () {
    fakeClaude(claudeAnswer([]));
    $stranger = User::factory()->admin()->create();
    $client = User::factory()->client($this->organization)->create();

    $this->actingAs($stranger)->post(route('admin.ai-drafts.store', $this->proposal))->assertNotFound();
    $this->actingAs($client)->post(route('admin.ai-drafts.store', $this->proposal))->assertForbidden();

    Http::assertNothingSent();
});

test('a sent proposal can’t be drafted on', function () {
    fakeClaude(claudeAnswer([]));
    $this->proposal->forceFill(['sent_at' => now(), 'sent_by_name' => 'Studio'])->save();

    $this->actingAs($this->admin)->post(route('admin.ai-drafts.store', $this->proposal))->assertForbidden();

    Http::assertNothingSent();
});
