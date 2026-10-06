<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Support\Launch\LaunchChecks;
use App\Support\Launch\LaunchUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

/**
 * "Run checks": the automated pre-flight checks, run while the admin waits
 * (no queue on the host, ADR 0002), rate limited (ADR 0007).
 */
class CheckRunController extends Controller
{
    public const RUNS_PER_WINDOW = 5;

    public const WINDOW_SECONDS = 600;

    public function store(Request $request, Project $project, LaunchChecks $checks): RedirectResponse
    {
        Gate::authorize('update', $project);

        $launch = $project->launch;
        abort_if($launch === null, 404);

        /** @var User $user */
        $user = $request->user();

        if (LaunchUrl::restrictedFor($user) && ! LaunchUrl::allowedInDemo($launch->url)) {
            return back()->withErrors(['checks' => 'In the demo, Launch Control can only check '.implode(' or ', LaunchUrl::demoHosts()).'. Change the site URL first.']);
        }

        // Per user and per launch, so neither one person nor one launch can
        // turn the server into a request cannon.
        $keys = ["launch-checks:user:{$user->id}", "launch-checks:launch:{$launch->id}"];

        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, self::RUNS_PER_WINDOW)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

                return back()->withErrors(['checks' => "That's a lot of runs in a short time. You can run the checks again in {$minutes} ".($minutes === 1 ? 'minute' : 'minutes').'.']);
            }
        }

        foreach ($keys as $key) {
            RateLimiter::hit($key, self::WINDOW_SECONDS);
        }

        // The checks have their own 25 s budget; leave PHP room to finish.
        if (function_exists('set_time_limit')) {
            @set_time_limit(60);
        }

        $run = $checks->run($launch, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Checks finished: {$run->summary()}."]);

        return back();
    }
}
