<?php

namespace App\Http\Controllers\Client;

use App\Enums\IntakeBudget;
use App\Enums\IntakeFeature;
use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Project;
use App\Models\User;
use App\Support\Activity;
use App\Support\Scope\IntakePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scope in Launchpad: the intake questionnaire (story 25).
 */
class ScopeController extends Controller
{
    public function show(Project $project): Response
    {
        Gate::authorize('viewAsClient', $project);

        $intake = $project->intake()->first();
        abort_if($intake === null, 404);

        return Inertia::render('client/scope/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'targetLaunchOn' => $project->target_launch_on?->toDateString(),
            ],
            'intake' => IntakePresenter::intake($intake),
            'options' => IntakePresenter::options(),
        ]);
    }

    /**
     * Saves the answers; with `submit`, also sends them to the studio. A
     * draft may be incomplete, a submitted intake may not.
     */
    public function updateIntake(Request $request, Intake $intake): RedirectResponse
    {
        Gate::authorize('fill', $intake);

        $pages = $request->input('pages');

        if (is_array($pages)) {
            // Empty rows (null after ConvertEmptyStringsToNull) are unused
            // rows, not answers.
            $request->merge(['pages' => array_values(array_filter(
                $pages,
                fn ($page) => $page !== null && (! is_string($page) || trim($page) !== ''),
            ))]);
        }

        $submitting = $request->boolean('submit');

        $data = $request->validate([
            'goals' => [$submitting ? 'required' : 'nullable', 'string', 'max:5000'],
            'pages' => [$submitting ? 'required' : 'nullable', 'array', 'max:30'],
            'pages.*' => ['required', 'string', 'max:80'],
            'features' => ['nullable', 'array'],
            'features.*' => ['distinct', Rule::enum(IntakeFeature::class)],
            'features_other' => ['nullable', 'string', 'max:200'],
            'budget' => [$submitting ? 'required' : 'nullable', Rule::enum(IntakeBudget::class)],
            'deadline_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'deadline_reason' => ['nullable', 'string', 'max:120'],
        ], [
            'goals.required' => 'Tell us what the website should do for you.',
            'pages.required' => 'Name at least one page.',
            'budget.required' => 'Choose a budget range (“Not sure yet” is fine).',
            'deadline_on.after_or_equal' => 'The deadline can’t be in the past.',
        ]);

        /** @var list<string> $features */
        $features = $data['features'] ?? [];

        $intake->goals = $data['goals'] ?? null;
        $intake->pages = array_values(array_map(trim(...), $data['pages'] ?? []));
        $intake->features = $features;
        $intake->features_other = $data['features_other'] ?? null;
        $intake->budget = isset($data['budget']) ? IntakeBudget::from($data['budget']) : null;
        $intake->deadline_on = $data['deadline_on'] ?? null;
        $intake->deadline_reason = $data['deadline_reason'] ?? null;

        if ($submitting) {
            /** @var User $client */
            $client = $request->user();

            $intake->submitted_at = now();
            $intake->submitted_by_id = $client->id;
            $intake->submitted_by_name = $client->name;
        }

        $intake->save();

        if ($submitting) {
            Activity::record('scope.intake_submitted', $intake, ['name' => $intake->project->name], visibleToClient: true);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $submitting
            ? 'Sent to the studio. Thank you!'
            : 'Draft saved. Send it when you’re ready.']);

        return to_route('client.scope.show', $intake->project_id);
    }
}
