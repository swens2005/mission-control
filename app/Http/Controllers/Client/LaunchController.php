<?php

namespace App\Http\Controllers\Client;

use App\Enums\ChecklistOwner;
use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\User;
use App\Support\Launch\ChecklistToggle;
use App\Support\Launch\LaunchPresenter;
use App\Support\Launch\LaunchSignoffs;
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

    /**
     * The client's go/no-go signature: any contact of the organization.
     */
    public function signoff(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('viewAsClient', $project);

        $launch = $project->launch;
        abort_if($launch === null, 404);

        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);

        /** @var User $client */
        $client = $request->user();
        LaunchSignoffs::sign($launch, $client, ChecklistOwner::Client, $validated['name'], $request->ip());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Thank you, your sign-off is recorded.']);

        return back();
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
