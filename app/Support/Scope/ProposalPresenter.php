<?php

namespace App\Support\Scope;

use App\Enums\DiscountType;
use App\Enums\LineItemKind;
use App\Models\LineItem;
use App\Models\Proposal;
use App\Models\ProposalLine;
use App\Support\Money\Money;
use App\Support\Money\ProposalTotals;
use App\Support\Money\Quantity;
use Illuminate\Support\Collection;

/**
 * A proposal as plain arrays for Inertia (stories 27 to 29). Amounts are
 * formatted here, once; the browser never does money maths.
 */
final class ProposalPresenter
{
    /**
     * @param  Collection<int, Proposal>|null  $versions  all of the project's versions, for the status
     * @return array<string, mixed>
     */
    public static function proposal(Proposal $proposal, ?Collection $versions = null): array
    {
        $totals = $proposal->totals();
        $discount = $proposal->discount();
        $project = $proposal->project;

        return [
            'id' => $proposal->id,
            'number' => $proposal->number,
            'title' => $proposal->title,
            'introduction' => $proposal->introduction,
            ...self::status($proposal, $versions ?? $project->proposals()->get()),
            'sentAt' => $proposal->sent_at?->toIso8601String(),
            'sentByName' => $proposal->sent_by_name,
            'projectName' => $project->name,
            'organizationName' => $project->organization->name,
            'studioName' => $project->workspace->name,
            'lines' => $proposal->lines->values()->map(
                fn (ProposalLine $line, int $i) => self::line($line, $totals->lineTotals[$i]),
            )->all(),
            'discount' => [
                'type' => $proposal->discount_type->value,
                'valueInput' => match ($proposal->discount_type) {
                    DiscountType::None => '',
                    DiscountType::Percentage => Quantity::format($proposal->discount_value),
                    DiscountType::Fixed => Money::toInput($proposal->discount_value),
                },
                'label' => $proposal->discount_label,
                'description' => $proposal->discountDescription(),
                'capped' => $discount !== null && $discount->exceeds($totals->subtotal),
            ],
            'totals' => self::totals($totals),
        ];
    }

    /**
     * Where a version stands: a draft, sent, or replaced by a later sent
     * version.
     *
     * @param  Collection<int, Proposal>  $versions
     * @return array{status: string, statusLabel: string}
     */
    public static function status(Proposal $proposal, Collection $versions): array
    {
        if (! $proposal->isSent()) {
            return ['status' => 'draft', 'statusLabel' => 'Draft'];
        }

        $newer = $versions
            ->filter(fn (Proposal $other) => $other->isSent() && $other->number > $proposal->number)
            ->sortBy('number')
            ->first();

        return $newer
            ? ['status' => 'superseded', 'statusLabel' => "Superseded by v{$newer->number}"]
            : ['status' => 'sent', 'statusLabel' => 'Sent'];
    }

    /**
     * The version list, newest first; clients see sent versions only.
     *
     * @param  Collection<int, Proposal>  $versions
     * @return list<array{id: int, number: int, status: string, statusLabel: string, sentAt: string|null, total: string|null}>
     */
    public static function versions(Collection $versions, bool $sentOnly = false): array
    {
        return array_values($versions
            ->filter(fn (Proposal $proposal) => ! $sentOnly || $proposal->isSent())
            ->sortByDesc('number')
            ->map(fn (Proposal $proposal) => [
                'id' => $proposal->id,
                'number' => $proposal->number,
                ...self::status($proposal, $versions),
                'sentAt' => $proposal->sent_at?->toIso8601String(),
                'total' => $proposal->isSent() ? Money::format((int) $proposal->total_cents) : null,
            ])
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function line(ProposalLine $line, int $totalCents): array
    {
        return [
            'id' => $line->id,
            'name' => $line->name,
            'description' => $line->description,
            'kind' => $line->kind->value,
            'kindLabel' => $line->kind->label(),
            'unitPrice' => Money::format($line->unit_price_cents),
            'unitPriceInput' => Money::toInput($line->unit_price_cents),
            'quantity' => Quantity::format($line->quantity),
            'unit' => $line->kind->unit(),
            'total' => Money::format($totalCents),
        ];
    }

    /**
     * @return array{subtotal: string, discount: string, net: string, vat: string, total: string, hasDiscount: bool}
     */
    public static function totals(ProposalTotals $totals): array
    {
        return [
            'subtotal' => Money::format($totals->subtotal),
            'discount' => Money::format(-$totals->discount),
            'net' => Money::format($totals->net()),
            'vat' => Money::format($totals->vat),
            'total' => Money::format($totals->total),
            'hasDiscount' => $totals->discount > 0,
        ];
    }

    /**
     * The library for "Add from library", grouped by kind.
     *
     * @param  Collection<int, LineItem>  $items
     * @return list<array{label: string, items: list<array{id: int, name: string, price: string, defaultQuantity: string}>}>
     */
    public static function library(Collection $items): array
    {
        $groups = [];

        foreach (LineItemKind::cases() as $kind) {
            $inKind = array_values($items->filter(fn (LineItem $item) => $item->kind === $kind)
                ->map(fn (LineItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => Money::format($item->unit_price_cents).($kind === LineItemKind::Hourly ? ' per hour' : ''),
                    'defaultQuantity' => Quantity::format($item->default_quantity),
                ])->all());

            if ($inKind !== []) {
                $groups[] = ['label' => $kind->label(), 'items' => $inKind];
            }
        }

        return $groups;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function kinds(): array
    {
        return array_map(
            fn (LineItemKind $kind) => ['value' => $kind->value, 'label' => $kind->label()],
            LineItemKind::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function discountTypes(): array
    {
        return array_map(
            fn (DiscountType $type) => ['value' => $type->value, 'label' => $type->label()],
            DiscountType::cases(),
        );
    }
}
