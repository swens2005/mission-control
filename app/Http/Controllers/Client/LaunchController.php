<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\User;
use App\Support\Launch\ChecklistToggle;
use App\Support\Launch\LaunchPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Launch Control in Launchpad: the client sees the checklist and ticks
 * their own items.
 */
class LaunchController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        Gate::authorize('viewAsClient', $project);

        $launch = $project->launch;
        abort_if($launch === null, 404);

        /** @var User $client */
        $client = $request->user();

        return Inertia::render('client/launch/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'targetLaunchOn' => $project->target_launch_on?->toDateString(),
            ],
            'launch' => LaunchPresenter::present($launch, $client),
        ]);
    }

    public function check(Request $request, ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('checkAsClient', $item);

        /** @var User $client */
        $client = $request->user();
        ChecklistToggle::set($item, $client, checked: true);

        return back();
    }

    public function uncheck(Request $request, ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('checkAsClient', $item);

        /** @var User $client */
        $client = $request->user();
        ChecklistToggle::set($item, $client, checked: false);

        return back();
    }
}
