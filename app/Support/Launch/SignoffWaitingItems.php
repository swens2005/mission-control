<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Enums\ProjectPhase;
use App\Models\Launch;
use App\Models\User;
use App\Support\Waiting\WaitingItem;

/**
 * "Waiting on you": sign off the launch, once the board is clear and the
 * client side hasn't signed yet.
 */
final class SignoffWaitingItems
{
    /**
     * @return list<WaitingItem>
     */
    public function __invoke(User $client): array
    {
        $launches = Launch::query()
            ->where('workspace_id', $client->workspace_id)
            ->whereHas('project', fn ($query) => $query
                ->active()
                ->where('organization_id', $client->organization_id)
                ->where('phase', '!=', ProjectPhase::Launched))
            ->whereDoesntHave('signoffs', fn ($query) => $query->active()->where('role', ChecklistOwner::Client))
            ->with('project')
            ->get();

        $items = [];

        foreach ($launches as $launch) {
            if (! GoNoGo::forLaunch($launch)->clear) {
                continue;
            }

            $items[] = new WaitingItem(
                title: 'Sign off the launch',
                module: 'Launch Control',
                projectId: $launch->project->id,
                projectName: $launch->project->name,
                url: route('client.launch.show', $launch->project),
                dueOn: $launch->project->target_launch_on,
            );
        }

        return $items;
    }
}
