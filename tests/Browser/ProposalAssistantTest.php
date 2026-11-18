<?php

use App\Models\LineItem;
use App\Models\Proposal;
use App\Support\Ai\ProposalAssistant;
use App\Support\Sandbox\SandboxFactory;
use Illuminate\Support\Facades\Http;

/*
 * Story 42: drafting with AI and reviewing the draft, in a real browser.
 * Http::fake() answers for the Claude API, so CI never calls it.
 */

beforeEach(function () {
    config(['services.anthropic.key' => 'test-key']);
    $this->sandbox = app(SandboxFactory::class)->create();

    $this->draft = Proposal::withoutGlobalScopes()
        ->where('workspace_id', $this->sandbox->workspace->id)
        ->whereNull('sent_at')
        ->firstOrFail();

    $items = LineItem::withoutGlobalScopes()->where('workspace_id', $this->sandbox->workspace->id)->get()->keyBy('name');

    Http::fake([ProposalAssistant::ENDPOINT => Http::response([
        'stop_reason' => 'tool_use',
        'content' => [[
            'type' => 'tool_use',
            'id' => 'toolu_test',
            'name' => ProposalAssistant::TOOL,
            'input' => [
                'introduction' => 'Thank you for telling us about the bakery’s seasons.',
                'lines' => [
                    ['line_item_id' => $items['Seasonal page']->id, 'quantity' => 4, 'reason' => 'Intake: a page for each season'],
                    ['line_item_id' => $items['Photo editing']->id, 'quantity' => 2, 'reason' => 'Intake: product photos'],
                ],
            ],
        ]],
        'usage' => ['input_tokens' => 2000, 'output_tokens' => 600],
    ])]);
});

test('the studio drafts with AI, keeps one line with a new quantity and adds it', function () {
    $this->actingAs($this->sandbox->admin);
    $before = $this->draft->lines()->withoutGlobalScopes()->count();

    $page = visit("/admin/projects/{$this->draft->project_id}/scope")
        ->assertSee('This demo has 3 AI drafts left.')
        ->press('Draft with AI')
        ->assertSee('Review the AI draft')
        ->assertSee('Intake: a page for each season')
        ->assertSee('€2,335.30');

    // Focus lands on the first suggestion (focusById waits two frames, so
    // give a slow CI runner a moment).
    $focused = '';

    for ($i = 0; $i < 20 && $focused !== 'ai-line-0-keep'; $i++) {
        $focused = (string) $page->script('document.activeElement?.id ?? ""');
        $focused === 'ai-line-0-keep' || $page->wait(0.1);
    }

    expect($focused)->toBe('ai-line-0-keep');

    $found = axeViolations($page, 'AI draft review');
    expect($found)->toBeEmpty(implode("\n", $found));

    $page->fill('#ai-line-0-quantity', '3')
        ->uncheck('#ai-line-1-keep')
        ->uncheck('#ai-use-introduction')
        ->press('Add the kept lines')
        ->assertSee('Added 1 line from the AI draft.')
        ->assertDontSee('Review the AI draft')
        ->assertNoJavaScriptErrors();

    $added = $this->draft->lines()->withoutGlobalScopes()->reorder()->latest('id')->first();

    expect($this->draft->lines()->withoutGlobalScopes()->count())->toBe($before + 1)
        ->and($added?->name)->toBe('Seasonal page')
        ->and($added?->quantity)->toBe(300);
});

test('a discarded draft adds nothing', function () {
    $this->actingAs($this->sandbox->admin);
    $before = $this->draft->lines()->withoutGlobalScopes()->count();

    visit("/admin/projects/{$this->draft->project_id}/scope")
        ->press('Draft with AI')
        ->assertSee('Review the AI draft')
        ->press('Discard the draft')
        ->assertDontSee('Review the AI draft')
        ->assertSee('This demo has 2 AI drafts left.')
        ->assertNoJavaScriptErrors();

    expect($this->draft->lines()->withoutGlobalScopes()->count())->toBe($before);
});
