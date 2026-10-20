<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectPhase;
use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\Proposal;
use App\Support\Activity;
use App\Support\Scope\IntakePresenter;
use App\Support\Scope\ProposalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scope for one project (studio side): the intake and the proposal.
 */
class ScopeController extends Controller
{
    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        $intake = $project->intake()->first();
        $versions = $project->proposals()->with(['lines', 'project.organization', 'project.workspace'])->get();
        // The accepted version once there is one; otherwise the newest: the
        // draft being built, or the last one sent.
        $proposal = $versions->first(fn (Proposal $version) => $version->isAccepted()) ?? $versions->first();

        return Inertia::render('admin/scope/show', [
            'project' => ProjectController::present($project),
            'canMoveToPalette' => $project->phase === ProjectPhase::Scope && (bool) $proposal?->isAccepted(),
            'intake' => $intake ? IntakePresenter::intake($intake) : null,
            'proposal' => $proposal ? ProposalPresenter::proposal($proposal, $versions) : null,
            'versions' => ProposalPresenter::versions($versions),
            // The newest sent version's change request, for "Make changes".
            'changesRequest' => self::changesRequest($versions->first(fn (Proposal $version) => $version->isSent())),
            'library' => $proposal && ! $proposal->isSent()
                ? ProposalPresenter::library(LineItem::query()->active()->orderBy('position')->orderBy('id')->get())
                : [],
            'kinds' => ProposalPresenter::kinds(),
            'discountTypes' => ProposalPresenter::discountTypes(),
        ]);
    }

    /**
     * @return array{version: int, note: string, byName: string|null, at: string|null}|null
     */
    private static function changesRequest(?Proposal $proposal): ?array
    {
        if ($proposal === null || ! $proposal->hasChangesRequested()) {
            return null;
        }

        return [
            'version' => $proposal->number,
            'note' => (string) $proposal->changes_note,
            'byName' => $proposal->changes_requested_by_name,
            'at' => $proposal->changes_requested_at?->toIso8601String(),
        ];
    }

    /**
     * After the client accepts, the project moves on to Palette Lab.
     */
    public function toPalette(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $accepted = $project->proposals()->whereNotNull('accepted_at')->exists();

        if ($project->phase !== ProjectPhase::Scope || ! $accepted) {
            throw ValidationException::withMessages(['phase' => 'The project moves on once the client has accepted a proposal.']);
        }

        $project->update(['phase' => ProjectPhase::Palette]);

        Activity::record('project.phase_changed', $project, [
            'name' => $project->name,
            'from' => ProjectPhase::Scope->label(),
            'to' => ProjectPhase::Palette->label(),
        ], visibleToClient: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$project->name} moved to Palette Lab."]);

        return to_route('admin.palette.show', $project);
    }

    /**
     * One version, read-only and printable.
     */
    public function version(Proposal $proposal): Response
    {
        Gate::authorize('view', $proposal);

        $proposal->load(['lines', 'project.organization', 'project.workspace']);

        return Inertia::render('admin/scope/version', [
            'project' => ProjectController::present($proposal->project),
            'proposal' => ProposalPresenter::proposal($proposal),
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
