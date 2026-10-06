<?php

namespace App\Support\Launch;

use App\Models\ChecklistItem;
use App\Models\User;
use App\Support\Activity;

/**
 * Ticks or unticks a checklist item for either portal; callers authorize
 * first. Repeating the same state is a no-op, so a double submit doesn't
 * add activity entries.
 */
final class ChecklistToggle
{
    public static function set(ChecklistItem $item, User $user, bool $checked): void
    {
        if ($item->isChecked() === $checked) {
            return;
        }

        $checked ? $item->check($user) : $item->uncheck();

        Activity::record($checked ? 'checklist.checked' : 'checklist.unchecked', $item->launch, [
            'name' => $item->label,
            'project' => $item->launch->project->name,
        ], visibleToClient: true, actor: $user);

        if (! $checked) {
            LaunchSignoffs::voidIfNotClear($item->launch, "{$item->label} was unticked.", $user);
        }
    }
}
