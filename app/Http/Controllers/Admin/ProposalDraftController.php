<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LineItemKind;
use App\Http\Controllers\Controller;
use App\Models\LineItem;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Models\User;
use App\Rules\QuarterQuantity;
use App\Support\Activity;
use App\Support\Ai\AssistantUnavailable;
use App\Support\Ai\DraftLimits;
use App\Support\Ai\ProposalAssistant;
use App\Support\Money\Money;
use App\Support\Money\Quantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Story 42: "Draft with AI". Drafting keeps the suggestion in the session;
 * nothing touches the proposal until the studio adds the lines it keeps.
 *
 * @phpstan-import-type Draft from ProposalAssistant
 */
class ProposalDraftController extends Controller
{
    public function store(Request $request, Proposal $proposal, ProposalAssistant $assistant, DraftLimits $limits): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        /** @var User $user */
        $user = $request->user();

        if (! $limits->configured()) {
            abort(404);
        }

        $intake = $proposal->project->intake()->first();

        if ($intake === null || ! $intake->isSubmitted()) {
            throw ValidationException::withMessages(['ai' => 'The assistant drafts from the client’s intake: wait until they’ve sent it.']);
        }

        $refusal = $limits->refusal($user->workspace);

        if ($refusal !== null) {
            throw ValidationException::withMessages(['ai' => $refusal]);
        }

        $library = LineItem::query()->active()->orderBy('position')->orderBy('id')->get();

        try {
            $draft = $assistant->draft($intake, $library, $user);
        } catch (AssistantUnavailable $e) {
            throw ValidationException::withMessages(['ai' => $e->getMessage()]);
        }

        $request->session()->put(self::key($proposal), $draft);

        return to_route('admin.scope.show', $proposal->project_id);
    }

    /**
     * Adds the lines the studio kept, at library prices, and the
     * introduction if asked.
     */
    public function apply(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $draft = self::current($request, $proposal);

        if ($draft === null) {
            throw ValidationException::withMessages(['ai' => 'This AI draft is no longer here. Draft again, or build the proposal by hand.']);
        }

        $data = $request->validate([
            'introduction' => ['nullable', 'string', 'max:5000'],
            'use_introduction' => ['boolean'],
            'lines' => ['array'],
            'lines.*.line_item_id' => ['required', 'integer'],
            'lines.*.keep' => ['boolean'],
            'lines.*.quantity' => ['nullable', 'string'],
        ]);

        $suggested = array_column($draft['lines'], null, 'lineItemId');
        $items = LineItem::query()->active()->whereKey(array_keys($suggested))->get()->keyBy('id');
        $kept = [];
        $errors = [];

        foreach ($data['lines'] ?? [] as $i => $line) {
            $id = (int) $line['line_item_id'];
            $item = $items->get($id);

            // Only the draft's own items, and only those still in the library.
            if (! isset($suggested[$id]) || ! $item instanceof LineItem || ! ($line['keep'] ?? false)) {
                continue;
            }

            $quantity = (string) ($line['quantity'] ?? '');
            $check = Validator::make(['quantity' => $quantity], [
                'quantity' => ['required', new QuarterQuantity(wholeOnly: $item->kind === LineItemKind::Fixed)],
            ]);

            if ($check->fails()) {
                $errors["lines.{$i}.quantity"] = (string) $check->errors()->first('quantity');

                continue;
            }

            $kept[] = [$item, (int) Quantity::parse($quantity)];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $useIntroduction = ($data['use_introduction'] ?? false) && trim((string) ($data['introduction'] ?? '')) !== '';

        if ($kept === [] && ! $useIntroduction) {
            throw ValidationException::withMessages(['ai' => 'Keep at least one line or the introduction, or discard the draft.']);
        }

        DB::transaction(function () use ($proposal, $kept, $useIntroduction, $data): void {
            $position = (int) $proposal->lines()->max('position');

            foreach ($kept as [$item, $quantity]) {
                $line = new ProposalLine;
                $line->proposal_id = $proposal->id;
                $line->line_item_id = $item->id;
                $line->name = $item->name;
                $line->description = $item->description;
                $line->kind = $item->kind;
                $line->unit_price_cents = $item->unit_price_cents;
                $line->quantity = $quantity;
                $line->position = ++$position;
                $line->save();
            }

            if ($useIntroduction) {
                $proposal->introduction = trim((string) $data['introduction']);
                $proposal->save();
            }
        });

        Activity::record('scope.ai_draft_created', $proposal, [
            'name' => $proposal->project->name,
            'version' => "v{$proposal->number}",
            'lines' => (string) count($kept),
        ]);

        $request->session()->forget(self::key($proposal));

        $count = count($kept);
        Inertia::flash('toast', ['type' => 'success', 'message' => $count === 1 ? 'Added 1 line from the AI draft.' : "Added {$count} lines from the AI draft."]);

        return to_route('admin.scope.show', $proposal->project_id);
    }

    public function destroy(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $request->session()->forget(self::key($proposal));

        return to_route('admin.scope.show', $proposal->project_id);
    }

    /**
     * The draft waiting for review on this proposal, if any.
     *
     * @return Draft|null
     */
    public static function current(Request $request, Proposal $proposal): ?array
    {
        $draft = $request->session()->get(self::key($proposal));

        /** @var Draft|null */
        return is_array($draft) && isset($draft['lines'], $draft['introduction']) ? $draft : null;
    }

    /**
     * The draft as the review screen shows it, with library names and
     * prices.
     *
     * @param  Draft  $draft
     * @return array{introduction: string, dropped: int, lines: list<array{lineItemId: int, name: string, kind: string, unit: string, price: string, quantity: string, reason: string}>}
     */
    public static function present(array $draft): array
    {
        $items = LineItem::query()->active()->whereKey(array_column($draft['lines'], 'lineItemId'))->get()->keyBy('id');
        $lines = [];

        foreach ($draft['lines'] as $line) {
            $item = $items->get($line['lineItemId']);

            if (! $item instanceof LineItem) {
                continue;
            }

            $lines[] = [
                'lineItemId' => $item->id,
                'name' => $item->name,
                'kind' => $item->kind->value,
                'unit' => $item->kind->unit(),
                'price' => Money::format($item->unit_price_cents).($item->kind === LineItemKind::Hourly ? ' per hour' : ''),
                'quantity' => Quantity::format($line['quantity']),
                'reason' => $line['reason'],
            ];
        }

        return [
            'introduction' => $draft['introduction'],
            'dropped' => $draft['dropped'],
            'lines' => $lines,
        ];
    }

    private static function key(Proposal $proposal): string
    {
        return "ai-draft.{$proposal->id}";
    }
}
