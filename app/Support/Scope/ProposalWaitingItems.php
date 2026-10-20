<?php

namespace App\Support\Scope;

use App\Enums\ProjectPhase;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Waiting\WaitingItem;

/**
 * "Waiting on you": review the latest sent proposal (story 28).
 */
final class ProposalWaitingItems
{
    /**
     * @return list<WaitingItem>
     */
    public function __invoke(User $client): array
    {
        $items = Proposal::query()
            ->where('workspace_id', $client->workspace_id)
            ->whereNotNull('sent_at')
            // Only the latest sent version of each project.
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('proposals as newer')
                ->whereColumn('newer.project_id', 'proposals.project_id')
                ->whereColumn('newer.number', '>', 'proposals.number')
                ->whereNotNull('newer.sent_at'))
            ->whereHas('project', fn ($query) => $query
                ->active()
                ->where('organization_id', $client->organization_id)
                ->where('phase', '!=', ProjectPhase::Launched))
            ->with('project')
            ->orderBy('id')
            ->get()
            ->map(fn (Proposal $proposal) => new WaitingItem(
                title: "Review proposal v{$proposal->number}",
                module: 'Scope',
                projectId: $proposal->project->id,
                projectName: $proposal->project->name,
                url: route('client.scope.show', $proposal->project),
                dueOn: $proposal->project->target_launch_on,
            ))
            ->all();

        return array_values($items);
    }
}
