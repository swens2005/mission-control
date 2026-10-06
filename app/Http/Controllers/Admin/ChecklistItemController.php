<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChecklistOwner;
use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use App\Support\Launch\ChecklistToggle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * The studio's side of the launch checklist: add, remove, tick, untick.
 */
class ChecklistItemController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $launch = $project->launch;
        abort_if($launch === null, 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'owner' => ['required', Rule::enum(ChecklistOwner::class)],
        ]);

        $item = new ChecklistItem([
            ...$validated,
            'position' => (int) $launch->checklistItems()->max('position') + 1,
        ]);
        $item->launch_id = $launch->id;
        $item->save();

        Activity::record('checklist.item_added', $launch, ['name' => $item->label, 'project' => $project->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$item->label} added."]);

        return back();
    }

    public function destroy(ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $item->delete();
        Activity::record('checklist.item_removed', $item->launch, ['name' => $item->label, 'project' => $item->launch->project->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$item->label} removed."]);

        return back();
    }

    public function check(Request $request, ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        /** @var User $user */
        $user = $request->user();
        ChecklistToggle::set($item, $user, checked: true);

        return back();
    }

    public function uncheck(Request $request, ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        /** @var User $user */
        $user = $request->user();
        ChecklistToggle::set($item, $user, checked: false);

        return back();
    }
}
