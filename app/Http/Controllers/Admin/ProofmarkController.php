<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoundStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Activity;
use App\Support\Proofmark\DesignFiles;
use App\Support\Proofmark\ProofmarkPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Proofmark for one project (studio side): its review rounds.
 */
class ProofmarkController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        /** @var User $user */
        $user = $request->user();
        $rounds = $project->reviewRounds()->with('designs')->get();

        // ?round=2 picks a round; otherwise the newest.
        $selected = $rounds->firstWhere('number', $request->integer('round')) ?? $rounds->first();

        return Inertia::render('admin/proofmark/show', [
            'project' => ProjectController::present($project),
            'rounds' => ProofmarkPresenter::rounds($rounds),
            'round' => $selected ? ProofmarkPresenter::round($selected) : null,
            'canStartRound' => $rounds->doesntContain(fn (ReviewRound $round) => $round->isDraft()),
            'quota' => $user->workspace->is_sandbox ? [
                'used' => DesignFiles::humanSize(DesignFiles::used($user->workspace)),
                'limit' => DesignFiles::humanSize(config()->integer('demo.upload_quota_bytes')),
            ] : null,
        ]);
    }

    /**
     * Starts the next round as a draft. One draft at a time: if there is
     * one already, that's where the studio is taken.
     */
    public function storeRound(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $draft = $project->reviewRounds()->where('status', RoundStatus::Draft)->first();

        if ($draft === null) {
            $draft = new ReviewRound;
            $draft->project_id = $project->id;
            $draft->number = (int) $project->reviewRounds()->max('number') + 1;
            $draft->status = RoundStatus::Draft;
            $draft->save();

            Activity::record('proofmark.round_created', $draft, ['name' => $project->name, 'round' => $draft->label()]);

            Inertia::flash('toast', ['type' => 'success', 'message' => "Round {$draft->label()} started."]);
        }

        return to_route('admin.proofmark.show', [$project, 'round' => $draft->number]);
    }

    /**
     * Sends a draft to the client. From now on its designs are frozen, and
     * the round it replaces (if any) is superseded: one round in review.
     */
    public function send(ReviewRound $round): RedirectResponse
    {
        Gate::authorize('update', $round);

        if (! $round->designs()->exists()) {
            throw ValidationException::withMessages(['send' => 'Add at least one design before sending the round.']);
        }

        $project = $round->project;

        DB::transaction(function () use ($round, $project): void {
            $replaced = $project->reviewRounds()->where('status', RoundStatus::InReview)->get();

            foreach ($replaced as $old) {
                $old->status = RoundStatus::Superseded;
                $old->save();
            }

            $round->status = RoundStatus::InReview;
            $round->sent_at = now();
            $round->save();

            Activity::record('proofmark.round_sent', $round, [
                'name' => $project->name,
                'round' => $round->label(),
                'replaces' => $replaced->first()?->label(),
            ], visibleToClient: true);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Round {$round->label()} sent to the client."]);

        return to_route('admin.proofmark.show', [$project, 'round' => $round->number]);
    }
}
