<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Project;
use App\Support\Activity;
use App\Support\Scope\IntakePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scope for one project (studio side): the intake, and later the proposal.
 */
class ScopeController extends Controller
{
    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        $intake = $project->intake()->first();

        return Inertia::render('admin/scope/show', [
            'project' => ProjectController::present($project),
            'intake' => $intake ? IntakePresenter::intake($intake) : null,
        ]);
    }

    /**
     * "Ask the client": makes the intake visible in Launchpad.
     */
    public function askIntake(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        if (! $project->intake()->exists()) {
            $intake = new Intake;
            $intake->project_id = $project->id;
            $intake->requested_at = now();
            $intake->save();

            Activity::record('scope.intake_requested', $intake, ['name' => $project->name], visibleToClient: true);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'The client can now fill in the intake.']);
        }

        return to_route('admin.scope.show', $project);
    }

    /**
     * Sends a submitted intake back to the client for changes.
     */
    public function reopenIntake(Intake $intake): RedirectResponse
    {
        Gate::authorize('reopen', $intake);

        if ($intake->isSubmitted()) {
            $intake->submitted_at = null;
            $intake->save();

            Activity::record('scope.intake_reopened', $intake, ['name' => $intake->project->name], visibleToClient: true);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Reopened. The client can change the answers and send them again.']);
        }

        return to_route('admin.scope.show', $intake->project_id);
    }
}
