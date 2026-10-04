<?php

namespace App\Support;

use App\Models\ActivityEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Writes to the shared audit trail. Every module calls this:
 *
 *     Activity::record('project.created', $project, ['name' => $project->name], visibleToClient: true);
 */
final class Activity
{
    /**
     * @param  Model  $subject  any model with a workspace_id
     * @param  array<string, mixed>  $properties  a snapshot (names, from/to) for the description
     */
    public static function record(
        string $event,
        Model $subject,
        array $properties = [],
        bool $visibleToClient = false,
        ?User $actor = null,
    ): ActivityEntry {
        $workspaceId = $subject->getAttribute('workspace_id');

        if (! is_int($workspaceId)) {
            throw new InvalidArgumentException('Activity needs a subject that belongs to a workspace.');
        }

        $actor ??= Auth::user() instanceof User ? Auth::user() : null;

        $entry = new ActivityEntry;
        $entry->forceFill([
            'workspace_id' => $workspaceId,
            'actor_id' => $actor?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'project_id' => $subject instanceof Project ? $subject->id : $subject->getAttribute('project_id'),
            'event' => $event,
            'properties' => $properties ?: null,
            'visible_to_client' => $visibleToClient,
        ])->save();

        return $entry;
    }
}
