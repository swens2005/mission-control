<?php

namespace App\Support;

use App\Enums\ProjectPhase;
use App\Models\ActivityEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Ai\DraftLimits;
use App\Support\Money\Money;
use App\Support\Waiting\WaitingItem;
use App\Support\Waiting\WaitingOnClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The flight deck (story 45): the admin dashboard, built only from data the
 * studio already has. Every query runs under the workspace scope, so it only
 * ever sees the signed-in studio.
 */
final class FlightDeck
{
    /** How many activity entries the mission log shows. */
    public const LOG_SIZE = 6;

    public function __construct(
        private readonly WaitingOnClient $waiting,
        private readonly DraftLimits $limits,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(User $user, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $missions = Project::query()
            ->active()
            ->where('phase', '!=', ProjectPhase::Launched)
            ->with(['organization', 'intake', 'proposals' => fn ($query) => $query->withCount('lines')])
            ->orderByRaw('target_launch_on is null')
            ->orderBy('target_launch_on')
            ->orderBy('id')
            ->get();

        $waiting = $this->waitingItems();
        $byProject = $waiting->groupBy(fn (WaitingItem $item) => $item->projectId);
        $clients = $missions->mapWithKeys(fn (Project $project) => [$project->id => $project->organization->name]);

        $accepted = Proposal::query()
            ->whereNotNull('accepted_at')
            ->whereBetween('accepted_at', [$now->startOfMonth(), $now])
            ->get(['id', 'total_cents']);

        $next = $missions->first(fn (Project $project) => $project->target_launch_on !== null
            && $project->target_launch_on->greaterThanOrEqualTo($now->startOfDay()));

        return [
            // The greeting and the date come from the viewer's own clock.
            'firstName' => self::firstName($user),
            'readouts' => [
                'inFlight' => $missions->count(),
                'waiting' => $waiting->count(),
                'nextLaunch' => $next === null ? null : [
                    'on' => $next->target_launch_on?->toDateString(),
                    'project' => $next->name,
                ],
                'acceptedThisMonth' => [
                    'amount' => Money::format((int) $accepted->sum('total_cents')),
                    'count' => $accepted->count(),
                ],
            ],
            'missions' => $missions->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'client' => $project->organization->name,
                'targetLaunchOn' => $project->target_launch_on?->toDateString(),
                'phase' => $project->phase->value,
                'phaseLabel' => $project->phase->label(),
                'step' => $project->phase->step(),
                'waiting' => $byProject->get($project->id, collect())
                    ->map(fn (WaitingItem $item) => $item->title)->values()->all(),
                'navigator' => $this->navigatorCanDraft($project),
                'urls' => [
                    'show' => route('admin.projects.show', $project),
                    'scope' => route('admin.scope.show', $project),
                    'palette' => route('admin.palette.show', $project),
                    'proofmark' => route('admin.proofmark.show', $project),
                    'launch' => route('admin.launch.show', $project),
                ],
            ])->values()->all(),
            'waiting' => $waiting->map(fn (WaitingItem $item) => [
                'title' => $item->title,
                'module' => $item->module,
                'client' => $clients->get($item->projectId),
                'projectName' => $item->projectName,
                'dueOn' => $item->dueOn?->toDateString(),
                'url' => route('admin.projects.show', $item->projectId),
            ])->values()->all(),
            'log' => ActivityEntry::query()
                ->with(['actor', 'project'])
                ->latest('created_at')
                ->latest('id')
                ->limit(self::LOG_SIZE)
                ->get()
                ->map(fn (ActivityEntry $entry) => ActivityFeed::present($entry))
                ->all(),
        ];
    }

    /**
     * Everything every client organization owes the studio, soonest due
     * first, only for projects still in flight.
     *
     * @return Collection<int, WaitingItem>
     */
    private function waitingItems(): Collection
    {
        return Organization::query()
            ->orderBy('name')
            ->get()
            ->flatMap(fn (Organization $organization) => $this->waiting->forOrganization($organization))
            ->sortBy(fn (WaitingItem $item) => $item->dueOn?->toDateString() ?? '9999-12-31')
            ->values();
    }

    /**
     * Navigator (story 42) has something to do: the intake is in and the
     * draft proposal is still empty.
     */
    private function navigatorCanDraft(Project $project): bool
    {
        if ($project->phase !== ProjectPhase::Scope || ! $this->limits->configured()) {
            return false;
        }

        $proposal = $project->proposals->first();

        return (bool) $project->intake?->isSubmitted()
            && $proposal !== null
            && ! $proposal->isSent()
            && (int) $proposal->getAttribute('lines_count') === 0;
    }

    private static function firstName(User $user): string
    {
        return explode(' ', trim($user->name))[0] ?: $user->name;
    }
}
