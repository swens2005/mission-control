<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Models\ChecklistItem;
use App\Models\Launch;
use App\Models\User;

/**
 * The launch as both portals receive it: explicit arrays, never models.
 */
final class LaunchPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Launch $launch, User $viewer): array
    {
        return [
            'id' => $launch->id,
            'url' => $launch->url,
            'checklist' => $launch->checklistItems
                ->map(fn (ChecklistItem $item) => self::item($item, $viewer))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function item(ChecklistItem $item, User $viewer): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'hint' => $item->hint,
            'owner' => $item->owner->value,
            'ownerLabel' => $item->owner->label(),
            'checked' => $item->isChecked(),
            'checkedAt' => $item->checked_at?->toIso8601String(),
            'checkedBy' => $item->checked_by_name,
            // The server checks again; this only decides what the UI offers.
            'canToggle' => $viewer->isAdmin() || $item->owner === ChecklistOwner::Client,
        ];
    }
}
