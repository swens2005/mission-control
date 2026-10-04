<?php

namespace App\Support;

use App\Models\ActivityEntry;

/**
 * Shapes activity entries for the pages that show them.
 */
final class ActivityFeed
{
    /**
     * @return array{id: int, actor: string, description: string, createdAt: string, project: array{id: int, name: string}|null}
     */
    public static function present(ActivityEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'actor' => $entry->actor->name ?? 'Mission Control',
            'description' => $entry->description(),
            'createdAt' => $entry->created_at->toIso8601String(),
            'project' => $entry->project === null ? null : [
                'id' => $entry->project->id,
                'name' => $entry->project->name,
            ],
        ];
    }
}
