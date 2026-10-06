<?php

namespace App\Support\Proofmark;

use App\Enums\ProjectPhase;
use App\Enums\RoundStatus;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Waiting\WaitingItem;

/**
 * "Waiting on you": review the round the studio sent, until it's approved
 * or replaced.
 */
final class RoundWaitingItems
{
    /**
     * @return list<WaitingItem>
     */
    public function __invoke(User $client): array
    {
        $items = ReviewRound::query()
            ->where('workspace_id', $client->workspace_id)
            ->where('status', RoundStatus::InReview)
            ->whereHas('project', fn ($query) => $query
                ->active()
                ->where('organization_id', $client->organization_id)
                ->where('phase', '!=', ProjectPhase::Launched))
            ->with('project')
            ->orderBy('id')
            ->get()
            ->map(fn (ReviewRound $round) => new WaitingItem(
                title: "Review design round {$round->label()}",
                module: 'Proofmark',
                projectId: $round->project->id,
                projectName: $round->project->name,
                url: route('client.proofmark.show', $round->project),
                dueOn: $round->project->target_launch_on,
            ))
            ->all();

        return array_values($items);
    }
}
