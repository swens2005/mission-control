<?php

namespace App\Http\Controllers\Client;

use App\Enums\ProjectPhase;
use App\Http\Controllers\Controller;
use App\Models\ActivityEntry;
use App\Models\Project;
use App\Models\User;
use App\Support\ActivityFeed;
use App\Support\Waiting\WaitingItem;
use App\Support\Waiting\WaitingOnClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Launchpad: the client's projects and what is waiting on them.
 */
class ProjectController extends Controller
{
    public function index(Request $request, WaitingOnClient $waiting): Response
    {
        /** @var User $client */
        $client = $request->user();

        $projects = Project::query()
            ->active()
            ->where('organization_id', $client->organization_id)
            // Launched projects (past dates) after the ones still in flight.
            ->orderByRaw("case when phase = 'launched' then 1 else 0 end")
            ->orderBy('target_launch_on')
            ->orderBy('name')
            ->get();

        return Inertia::render('client/home', [
            'organizationName' => $client->organization?->name,
            'projects' => $projects->map(fn (Project $project) => $this->present($project))->values(),
            'steps' => self::steps(),
            'waiting' => array_map(fn (WaitingItem $item) => $item->toArray(), $waiting->for($client)),
        ]);
    }

    public function show(Project $project): Response
    {
        Gate::authorize('viewAsClient', $project);

        return Inertia::render('client/projects/show', [
            'project' => $this->present($project),
            'steps' => self::steps(),
            // Only what the studio marked for clients.
            'activity' => ActivityEntry::query()
                ->visibleToClient()
                ->with(['actor', 'project'])
                ->where('project_id', $project->id)
                ->latest('created_at')
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (ActivityEntry $entry) => ActivityFeed::present($entry)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'phase' => $project->phase->value,
            'phaseLabel' => $project->phase->label(),
            'step' => $project->phase->step(),
            'targetLaunchOn' => $project->target_launch_on?->toDateString(),
        ];
    }

    /**
     * The four module phases, in order, for the step indicator.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function steps(): array
    {
        $steps = [];

        foreach (ProjectPhase::cases() as $phase) {
            if ($phase !== ProjectPhase::Launched) {
                $steps[] = ['value' => $phase->value, 'label' => $phase->label()];
            }
        }

        return $steps;
    }
}
