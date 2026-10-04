<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\ActivityEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Support\Activity;
use App\Support\ActivityFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Project::class);

        $filters = $request->validate([
            'organization' => ['nullable', 'integer'],
            'phase' => ['nullable', Rule::enum(ProjectPhase::class)],
            'archived' => ['nullable', 'boolean'],
        ]);

        $projects = Project::query()
            ->with('organization')
            ->when(! $request->boolean('archived'), fn ($query) => $query->active())
            ->when($filters['organization'] ?? null, fn ($query, $id) => $query->where('organization_id', $id))
            ->when($filters['phase'] ?? null, fn ($query, $phase) => $query->where('phase', $phase))
            ->orderBy('target_launch_on')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Project $project) => self::present($project));

        return Inertia::render('admin/projects/index', [
            'projects' => $projects,
            'filters' => [
                'organization' => isset($filters['organization']) ? (int) $filters['organization'] : null,
                'phase' => $filters['phase'] ?? null,
                'archived' => $request->boolean('archived'),
            ],
            'organizations' => $this->organizationOptions(includeArchived: true),
            'phases' => self::phaseOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Project::class);

        return Inertia::render('admin/projects/create', [
            'organizations' => $this->organizationOptions(),
            'phases' => self::phaseOptions(),
            'organizationId' => $request->integer('organization') ?: null,
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $project = Project::create($request->validated());
        Activity::record('project.created', $project, ['name' => $project->name], visibleToClient: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$project->name} created."]);

        return to_route('admin.projects.show', $project);
    }

    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('admin/projects/show', [
            'project' => self::present($project),
            'activity' => ActivityEntry::query()
                ->with(['actor', 'project'])
                ->where('project_id', $project->id)
                ->latest('created_at')
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (ActivityEntry $entry) => ActivityFeed::present($entry)),
        ]);
    }

    public function edit(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('admin/projects/edit', [
            'project' => self::present($project),
            'organizations' => $this->organizationOptions(),
            'phases' => self::phaseOptions(),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $phaseBefore = $project->phase;
        $project->update($request->validated());

        if ($project->wasChanged('phase')) {
            Activity::record('project.phase_changed', $project, [
                'name' => $project->name,
                'from' => $phaseBefore->label(),
                'to' => $project->phase->label(),
            ], visibleToClient: true);
        }

        if (array_diff(array_keys($project->getChanges()), ['phase', 'updated_at']) !== []) {
            Activity::record('project.updated', $project, ['name' => $project->name]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Changes saved.']);

        return to_route('admin.projects.show', $project);
    }

    public function archive(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->forceFill(['archived_at' => now()])->save();
        Activity::record('project.archived', $project, ['name' => $project->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$project->name} archived."]);

        return to_route('admin.projects.show', $project);
    }

    public function unarchive(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->forceFill(['archived_at' => null])->save();
        Activity::record('project.restored', $project, ['name' => $project->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$project->name} restored."]);

        return to_route('admin.projects.show', $project);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'phase' => $project->phase->value,
            'phaseLabel' => $project->phase->label(),
            'targetLaunchOn' => $project->target_launch_on?->toDateString(),
            'archived' => $project->isArchived(),
            'organization' => [
                'id' => $project->organization->id,
                'name' => $project->organization->name,
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function phaseOptions(): array
    {
        return array_map(
            fn (ProjectPhase $phase) => ['value' => $phase->value, 'label' => $phase->label()],
            ProjectPhase::cases(),
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function organizationOptions(bool $includeArchived = false): array
    {
        return array_values(Organization::query()
            ->when(! $includeArchived, fn ($query) => $query->active())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $organization) => ['id' => $organization->id, 'name' => $organization->name])
            ->all());
    }
}
