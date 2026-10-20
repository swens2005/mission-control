<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LineItemKind;
use App\Http\Controllers\Controller;
use App\Models\LineItem;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Rules\Price;
use App\Rules\QuarterQuantity;
use App\Support\Activity;
use App\Support\Money\Money;
use App\Support\Money\Quantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Scope: the lines of a draft proposal (story 27). A line is a copy, so the
 * library can change without touching proposals.
 */
class ProposalLineController extends Controller
{
    /**
     * "Add from library": copies the item into the proposal.
     */
    public function storeFromLibrary(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $request->validate([
            'line_item_id' => ['required', 'integer'],
        ], ['line_item_id.required' => 'Choose an item from the library.']);

        // The workspace scope keeps other studios' items out; archived items
        // aren't offered any more.
        $item = LineItem::query()->active()->find($request->integer('line_item_id'));

        if ($item === null) {
            throw ValidationException::withMessages(['line_item_id' => 'Choose an item from the library.']);
        }

        $data = $request->validate([
            'quantity' => ['nullable', new QuarterQuantity(wholeOnly: $item->kind === LineItemKind::Fixed)],
        ]);

        $line = new ProposalLine;
        $line->proposal_id = $proposal->id;
        $line->line_item_id = $item->id;
        $line->name = $item->name;
        $line->description = $item->description;
        $line->kind = $item->kind;
        $line->unit_price_cents = $item->unit_price_cents;
        $line->quantity = isset($data['quantity']) ? (int) Quantity::parse($data['quantity']) : $item->default_quantity;

        return $this->add($proposal, $line);
    }

    /**
     * A custom line, without the library.
     */
    public function store(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $data = $request->validate([
            ...$this->rules($request->input('kind') === LineItemKind::Fixed->value),
            'kind' => ['required', Rule::enum(LineItemKind::class)],
        ]);

        $line = new ProposalLine;
        $line->proposal_id = $proposal->id;
        $line->kind = LineItemKind::from($data['kind']);
        $this->fill($line, $data);

        return $this->add($proposal, $line);
    }

    public function update(Request $request, ProposalLine $line): RedirectResponse
    {
        Gate::authorize('update', $line->proposal);

        $data = $request->validate($this->rules($line->kind === LineItemKind::Fixed));

        $this->fill($line, $data);

        if ($line->isDirty()) {
            $line->save();

            Activity::record('scope.line_changed', $line->proposal, $this->snapshot($line));
        }

        return to_route('admin.scope.show', $line->proposal->project_id);
    }

    public function move(Request $request, ProposalLine $line): RedirectResponse
    {
        Gate::authorize('update', $line->proposal);

        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($line, $direction): void {
            $ids = $line->proposal->lines()->pluck('id')->all();
            $from = (int) array_search($line->id, $ids, true);
            $to = $direction === 'up' ? $from - 1 : $from + 1;

            if (! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $position => $id) {
                ProposalLine::whereKey($id)->update(['position' => $position]);
            }
        });

        return to_route('admin.scope.show', $line->proposal->project_id);
    }

    public function destroy(ProposalLine $line): RedirectResponse
    {
        $proposal = $line->proposal;

        Gate::authorize('update', $proposal);

        $line->delete();

        Activity::record('scope.line_removed', $proposal, $this->snapshot($line));

        return to_route('admin.scope.show', $proposal->project_id);
    }

    private function add(Proposal $proposal, ProposalLine $line): RedirectResponse
    {
        $line->position = (int) $proposal->lines()->max('position') + 1;
        $line->save();

        Activity::record('scope.line_added', $proposal, $this->snapshot($line));

        return to_route('admin.scope.show', $proposal->project_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $wholeOnly): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit_price' => ['required', new Price],
            'quantity' => ['required', new QuarterQuantity($wholeOnly)],
        ];
    }

    /**
     * @param  array<string, mixed>  $data  validated by rules()
     */
    private function fill(ProposalLine $line, array $data): void
    {
        $line->name = trim((string) $data['name']);
        $line->description = isset($data['description']) ? trim((string) $data['description']) : null;
        $line->unit_price_cents = (int) Money::parse((string) $data['unit_price']);
        $line->quantity = (int) Quantity::parse((string) $data['quantity']);
    }

    /**
     * @return array<string, string>
     */
    private function snapshot(ProposalLine $line): array
    {
        return [
            'name' => $line->proposal->project->name,
            'version' => "v{$line->proposal->number}",
            'line' => $line->name,
        ];
    }
}
