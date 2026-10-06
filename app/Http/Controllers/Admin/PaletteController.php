<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Models\Project;
use App\Support\Activity;
use App\Support\Palette\PalettePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Palette Lab for one project (studio side).
 */
class PaletteController extends Controller
{
    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        $kit = $project->brandKit()->with('colors')->first();

        return Inertia::render('admin/palette/show', [
            'project' => ProjectController::present($project),
            'kit' => $kit ? PalettePresenter::kit($kit) : null,
            'roles' => PalettePresenter::roles(),
            'typeOptions' => PalettePresenter::typeOptions(),
        ]);
    }

    public function store(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        if (! $project->brandKit()->exists()) {
            $kit = new BrandKit;
            $kit->project_id = $project->id;
            $kit->save();

            Activity::record('palette.kit_created', $kit, ['name' => $project->name]);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand kit started.']);
        }

        return to_route('admin.palette.show', $project);
    }
}
