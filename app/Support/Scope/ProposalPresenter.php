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
     * @return array<string, mixed>
     */
    public static function proposal(Proposal $proposal): array
    {
        $totals = $proposal->totals();
        $discount = $proposal->discount();

        return [
            'id' => $proposal->id,
            'number' => $proposal->number,
            'title' => $proposal->title,
            'introduction' => $proposal->introduction,
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
