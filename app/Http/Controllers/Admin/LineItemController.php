<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LineItemKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LineItemRequest;
use App\Models\LineItem;
use App\Support\Activity;
use App\Support\Money\Money;
use App\Support\Money\Quantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scope: the studio's line-item library, its price list (story 26).
 */
class LineItemController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', LineItem::class);

        $items = LineItem::query()->orderBy('position')->orderBy('id')->get();

        return Inertia::render('admin/scope/library', [
            'items' => $items->filter(fn (LineItem $item) => ! $item->isArchived())
                ->map(fn (LineItem $item) => self::present($item))->values(),
            'archived' => $items->filter(fn (LineItem $item) => $item->isArchived())
                ->map(fn (LineItem $item) => self::present($item))->values(),
            'kinds' => array_map(
                fn (LineItemKind $kind) => ['value' => $kind->value, 'label' => $kind->label()],
                LineItemKind::cases(),
            ),
        ]);
    }

    public function store(LineItemRequest $request): RedirectResponse
    {
        Gate::authorize('create', LineItem::class);

        $item = new LineItem;
        $item->forceFill($request->item());
        $item->position = (int) LineItem::query()->max('position') + 1;
        $item->save();

        Activity::record('scope.item_added', $item, ['name' => $item->name, 'price' => Money::format($item->unit_price_cents)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$item->name} added to the library."]);

        return to_route('admin.line-items.index');
    }

    public function update(LineItemRequest $request, LineItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $item->forceFill($request->item());

        if ($item->isDirty()) {
            $item->save();

            Activity::record('scope.item_changed', $item, ['name' => $item->name, 'price' => Money::format($item->unit_price_cents)]);
        }

        return to_route('admin.line-items.index');
    }

    public function move(Request $request, LineItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        DB::transaction(function () use ($item, $direction): void {
            $ids = LineItem::query()->active()->orderBy('position')->orderBy('id')->pluck('id')->all();
            $from = array_search($item->id, $ids, true);
            $to = $direction === 'up' ? (int) $from - 1 : (int) $from + 1;

            if ($from === false || ! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $position => $id) {
                LineItem::whereKey($id)->update(['position' => $position]);
            }
        });

        return to_route('admin.line-items.index');
    }

    /**
     * Archived items leave the "Add from library" list; proposals that
     * already use them keep their own copy.
     */
    public function archive(LineItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        if (! $item->isArchived()) {
            $item->archived_at = now();
            $item->save();

            Activity::record('scope.item_archived', $item, ['name' => $item->name]);

            Inertia::flash('toast', ['type' => 'success', 'message' => "{$item->name} archived."]);
        }

        return to_route('admin.line-items.index');
    }

    public function restore(LineItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        if ($item->isArchived()) {
            $item->archived_at = null;
            $item->position = (int) LineItem::query()->max('position') + 1;
            $item->save();

            Activity::record('scope.item_restored', $item, ['name' => $item->name]);

            Inertia::flash('toast', ['type' => 'success', 'message' => "{$item->name} is back in the library."]);
        }

        return to_route('admin.line-items.index');
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(LineItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'kind' => $item->kind->value,
            'kindLabel' => $item->kind->label(),
            'unitPrice' => Money::format($item->unit_price_cents),
            'unitPriceInput' => Money::toInput($item->unit_price_cents),
            'defaultQuantity' => Quantity::format($item->default_quantity),
            'unit' => $item->kind->unit(),
        ];
    }
}
