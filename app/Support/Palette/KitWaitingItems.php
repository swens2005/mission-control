<?php

namespace App\Support\Palette;

use App\Enums\ProjectPhase;
use App\Models\BrandKit;
use App\Models\User;
use App\Support\Waiting\WaitingItem;

/**
 * "Waiting on you": approve a shared brand kit (story 24).
 */
final class KitWaitingItems
{
    /**
     * @return list<WaitingItem>
     */
    public function __invoke(User $client): array
    {
        $items = BrandKit::query()
            ->where('workspace_id', $client->workspace_id)
            ->whereNotNull('shared_at')
            ->whereNull('approved_at')
            ->whereHas('project', fn ($query) => $query
                ->active()
                ->where('organization_id', $client->organization_id)
                ->where('phase', '!=', ProjectPhase::Launched))
            ->with('project')
            ->orderBy('id')
            ->get()
            ->map(fn (BrandKit $kit) => new WaitingItem(
                title: 'Approve the brand kit',
                module: 'Palette Lab',
                projectId: $kit->project->id,
                projectName: $kit->project->name,
                url: route('client.palette.show', $kit->project),
                dueOn: $kit->project->target_launch_on,
            ))
            ->all();

        return array_values($items);
    }
}
