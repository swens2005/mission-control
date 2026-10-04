<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganizationRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Organization::class);

        $search = trim((string) $request->query('search', ''));
        $showArchived = $request->boolean('archived');

        $organizations = Organization::query()
            ->when(! $showArchived, fn ($query) => $query->active())
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->withCount([
                'projects' => fn ($query) => $query->active(),
                'contacts',
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Organization $organization) => [
                ...$this->present($organization),
                'projectsCount' => $organization->projects_count,
                'contactsCount' => $organization->contacts_count,
            ]);

        return Inertia::render('admin/organizations/index', [
            'organizations' => $organizations,
            'filters' => ['search' => $search, 'archived' => $showArchived],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Organization::class);

        return Inertia::render('admin/organizations/create');
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Organization::class);

        $organization = Organization::create($request->validated());
        Activity::record('organization.created', $organization, ['name' => $organization->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$organization->name} added."]);

        return to_route('admin.organizations.show', $organization);
    }

    public function show(Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        return Inertia::render('admin/organizations/show', [
            'organization' => $this->present($organization),
            'projects' => $organization->projects()->with('organization')->orderBy('archived_at')->orderBy('name')->get()
                ->map(fn (Project $project) => ProjectController::present($project)),
            'contacts' => $organization->contacts()->orderBy('name')->get()
                ->map(fn (User $contact) => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                ]),
        ]);
    }

    public function edit(Organization $organization): Response
    {
        Gate::authorize('update', $organization);

        return Inertia::render('admin/organizations/edit', [
            'organization' => $this->present($organization),
        ]);
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $organization->update($request->validated());

        if ($organization->wasChanged()) {
            Activity::record('organization.updated', $organization, ['name' => $organization->name]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Changes saved.']);

        return to_route('admin.organizations.show', $organization);
    }

    public function archive(Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $organization->forceFill(['archived_at' => now()])->save();
        Activity::record('organization.archived', $organization, ['name' => $organization->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$organization->name} archived."]);

        return to_route('admin.organizations.show', $organization);
    }

    public function unarchive(Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $organization->forceFill(['archived_at' => null])->save();
        Activity::record('organization.restored', $organization, ['name' => $organization->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$organization->name} restored."]);

        return to_route('admin.organizations.show', $organization);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'websiteUrl' => $organization->website_url,
            'archived' => $organization->isArchived(),
        ];
    }
}
