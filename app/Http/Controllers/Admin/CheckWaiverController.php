<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Support\Launch\Checks\CheckRegistry;
use App\Support\Launch\LaunchChecks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Waiving a failing or warning check, with a reason, and withdrawing it.
 */
class CheckWaiverController extends Controller
{
    public function store(Request $request, Project $project, string $check, LaunchChecks $checks): RedirectResponse
    {
        Gate::authorize('update', $project);

        $launch = $project->launch;
        abort_if($launch === null || ! array_key_exists($check, CheckRegistry::labels()), 404);

        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        /** @var User $user */
        $user = $request->user();
        $checks->waive($launch, $check, $validated['reason'], $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => CheckRegistry::labels()[$check].' waived.']);

        return back();
    }

    public function destroy(Request $request, Project $project, string $check, LaunchChecks $checks): RedirectResponse
    {
        Gate::authorize('update', $project);

        $launch = $project->launch;
        abort_if($launch === null || ! array_key_exists($check, CheckRegistry::labels()), 404);

        /** @var User $user */
        $user = $request->user();
        $checks->withdraw($launch, $check, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Waiver withdrawn.']);

        return back();
    }
}
