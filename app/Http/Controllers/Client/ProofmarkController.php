<?php

namespace App\Http\Controllers\Client;

use App\Enums\RoundStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Proofmark\ProofmarkPresenter;
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
            ->with('designs')
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
}
