<?php

namespace App\Support\Scope;

use App\Enums\ProjectPhase;
use App\Models\Intake;
use App\Models\User;
use App\Support\Waiting\WaitingItem;

/**
 * "Waiting on you": fill in an intake the studio asked for (story 25).
 */
final class IntakeWaitingItems
{
    /**
     * @return list<WaitingItem>
     */
    public function __invoke(User $client): array
    {
        $items = Intake::query()
            ->where('workspace_id', $client->workspace_id)
            ->whereNull('submitted_at')
            ->whereHas('project', fn ($query) => $query
                ->active()
                ->where('organization_id', $client->organization_id)
                ->where('phase', '!=', ProjectPhase::Launched))
            ->with('project')
            ->orderBy('id')
            ->get()
            ->map(fn (Intake $intake) => new WaitingItem(
                title: 'Fill in the intake',
                module: 'Scope',
                projectId: $intake->project->id,
                projectName: $intake->project->name,
                url: route('client.scope.show', $intake->project),
                dueOn: $intake->project->target_launch_on,
            ))
            ->all();

        return array_values($items);
    }
}
