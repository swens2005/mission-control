<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChecklistOwner;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Support\Launch\LaunchSignoffs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The studio's go/no-go signature.
 */
class SignoffController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $launch = $project->launch;
        abort_if($launch === null, 404);

        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);

        /** @var User $user */
        $user = $request->user();
        LaunchSignoffs::sign($launch, $user, ChecklistOwner::Studio, $validated['name'], $request->ip());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Signed for the studio.']);

        return back();
    }
}
