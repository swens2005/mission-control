<?php

namespace App\Http\Controllers\Client;

use App\Enums\RoundStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ReviewRound;
use App\Models\User;
use App\Support\Activity;
use App\Support\Proofmark\ProofmarkPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Proofmark in Launchpad: the rounds the studio has sent. Drafts stay
 * invisible (ReviewRoundPolicy::viewAsClient).
 */
class ProofmarkController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        Gate::authorize('viewAsClient', $project);

        $rounds = $project->reviewRounds()
            ->where('status', '!=', RoundStatus::Draft)
            ->with('designs.comments')
            ->get();

        abort_if($rounds->isEmpty(), 404);

        $selected = $rounds->firstWhere('number', $request->integer('round')) ?? $rounds->first();

        return Inertia::render('client/proofmark/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'targetLaunchOn' => $project->target_launch_on?->toDateString(),
            ],
            'rounds' => ProofmarkPresenter::rounds($rounds),
            'round' => ProofmarkPresenter::round($selected),
        ]);
    }

    /**
     * The client approves the round in review. Open comments stay open, for
     * the record; the confirmation in the browser says how many.
     */
    public function approve(Request $request, ReviewRound $round): RedirectResponse
    {
        Gate::authorize('approve', $round);

        /** @var User $client */
        $client = $request->user();

        $round->status = RoundStatus::Approved;
        $round->approved_at = now();
        $round->approved_by_id = $client->id;
        $round->approved_by_name = $client->name;
        $round->approved_ip = $request->ip();
        $round->save();

        Activity::record('proofmark.round_approved', $round, [
            'name' => $round->project->name,
            'round' => $round->label(),
        ], visibleToClient: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Round {$round->label()} approved. Thank you!"]);

        return to_route('client.proofmark.show', [$round->project_id, 'round' => $round->number]);
    }
}
