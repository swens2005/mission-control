<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Launch;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use App\Support\Launch\DefaultChecklist;
use App\Support\Launch\LaunchPresenter;
use App\Support\Launch\LaunchUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Launch Control for one project (studio side).
 */
class LaunchController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        /** @var User $user */
        $user = $request->user();
        $launch = $project->launch;

        return Inertia::render('admin/launch/show', [
            'project' => ProjectController::present($project),
            'launch' => $launch ? LaunchPresenter::present($launch, $user) : null,
            'isSandbox' => LaunchUrl::restrictedFor($user),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        if ($project->launch()->exists()) {
            return to_route('admin.launch.show', $project);
        }

        $validated = $request->validate(['url' => LaunchUrl::rules($request->user())]);

        DB::transaction(function () use ($project, $validated): void {
            $launch = new Launch(['url' => $validated['url']]);
            $launch->project_id = $project->id;
            $launch->save();

            DefaultChecklist::addTo($launch);

            Activity::record('launch.created', $launch, ['name' => $project->name], visibleToClient: true);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Launch prepared.']);

        return to_route('admin.launch.show', $project);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $launch = $project->launch;
        abort_if($launch === null, 404);

        $launch->update($request->validate(['url' => LaunchUrl::rules($request->user())]));

        if ($launch->wasChanged('url')) {
            Activity::record('launch.updated', $launch, ['name' => $project->name, 'url' => $launch->url]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Site URL saved.']);

        return to_route('admin.launch.show', $project);
    }
}
