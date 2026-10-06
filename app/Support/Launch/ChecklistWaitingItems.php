<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Enums\ProjectPhase;
use App\Models\Launch;
use App\Models\User;
use App\Support\Waiting\WaitingItem;

/**
 * "Waiting on you": one item per launch that still has open client-owned
 * checklist items.
 */
final class ChecklistWaitingItems
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
            ->withCount(['checklistItems as open_client_items' => fn ($query) => $query
                ->where('owner', ChecklistOwner::Client)
                ->whereNull('checked_at')])
            ->with('project')
            ->get();

        $items = [];

        foreach ($launches as $launch) {
            $open = (int) $launch->getAttribute('open_client_items');

            if ($open === 0) {
                continue;
            }

            $items[] = new WaitingItem(
                title: 'Finish your launch checklist ('.$open.' '.($open === 1 ? 'item' : 'items').' left)',
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
