<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Models\ChecklistItem;
use App\Models\Launch;

/**
 * The manual pre-flight items every new launch starts with.
 */
final class DefaultChecklist
{
    /**
     * @return list<array{label: string, hint: string, owner: ChecklistOwner}>
     */
    public static function items(): array
    {
        return [
            ['label' => '404 page', 'hint' => "A page that doesn't exist shows a friendly message and a way back.", 'owner' => ChecklistOwner::Studio],
            ['label' => 'Favicon', 'hint' => 'The icon shows in the browser tab and when saved to a phone.', 'owner' => ChecklistOwner::Studio],
            ['label' => 'Backups', 'hint' => 'Automatic backups are on, and one restore has been tried.', 'owner' => ChecklistOwner::Studio],
            ['label' => 'Forms tested', 'hint' => 'Send every form once and check the message arrives where you expect.', 'owner' => ChecklistOwner::Client],
            ['label' => 'DNS TTL lowered', 'hint' => "A day before launch, lower the domain's TTL to 5 minutes so the switch is quick.", 'owner' => ChecklistOwner::Client],
            ['label' => 'Analytics consent', 'hint' => 'The cookie or consent notice matches what the site really tracks.', 'owner' => ChecklistOwner::Client],
        ];
    }

    public static function addTo(Launch $launch): void
    {
        foreach (self::items() as $position => $item) {
            $checklistItem = new ChecklistItem([...$item, 'position' => $position]);
            $checklistItem->launch_id = $launch->id;
            $checklistItem->workspace_id = $launch->workspace_id;
            $checklistItem->save();
        }
    }
}
