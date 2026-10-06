<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BrandKit;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use App\Support\Palette\PalettePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Palette Lab in Launchpad: the shared brand kit as a style guide, and its
 * approval (story 24).
 */
class PaletteController extends Controller
{
    public function show(Project $project): Response
    {
        Gate::authorize('viewAsClient', $project);

        $kit = $project->brandKit()->with(['colors', 'project'])->first();
        abort_if($kit === null, 404);
        Gate::authorize('viewAsClient', $kit);

        return Inertia::render('client/palette/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'targetLaunchOn' => $project->target_launch_on?->toDateString(),
            ],
            'guide' => PalettePresenter::styleGuide($kit),
        ]);
    }

    public function approve(Request $request, BrandKit $kit): RedirectResponse
    {
        Gate::authorize('approve', $kit);

        /** @var User $client */
        $client = $request->user();

        $kit->approved_at = now();
        $kit->approved_by_id = $client->id;
        $kit->approved_by_name = $client->name;
        $kit->approved_ip = $request->ip();
        $kit->save();

        Activity::record('palette.kit_approved', $kit, ['name' => $kit->project->name], visibleToClient: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand kit approved. Thank you!']);

        return to_route('client.palette.show', $kit->project_id);
    }
}
