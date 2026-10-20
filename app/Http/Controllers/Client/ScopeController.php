<?php

namespace App\Http\Controllers\Client;

use App\Enums\IntakeBudget;
use App\Enums\IntakeFeature;
use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Activity;
use App\Support\Scope\IntakePresenter;
use App\Support\Scope\ProposalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Scope in Launchpad: the intake questionnaire (story 25) and the sent
 * proposal versions (story 28).
 */
class ScopeController extends Controller
{
    public function show(Project $project): Response
    {
        Gate::authorize('viewAsClient', $project);

        $intake = $project->intake()->first();
        // Sent versions only; a draft is never shown to the client.
        $versions = $project->proposals()->whereNotNull('sent_at')
            ->with(['lines', 'project.organization', 'project.workspace'])->get();
        $proposal = $versions->first();

        abort_if($intake === null && $proposal === null, 404);

        return Inertia::render('client/scope/show', [
            'project' => $this->project($project),
            'intake' => $intake ? IntakePresenter::intake($intake) : null,
            'options' => IntakePresenter::options(),
            'proposal' => $proposal ? ProposalPresenter::proposal($proposal, $versions) : null,
            'versions' => ProposalPresenter::versions($versions),
        ]);
    }

    /**
     * One sent version, read-only and printable.
     */
    public function version(Proposal $proposal): Response
    {
        Gate::authorize('viewAsClient', $proposal);

        $proposal->load(['lines', 'project.organization', 'project.workspace']);

        return Inertia::render('client/scope/version', [
            'project' => $this->project($proposal->project),
            'proposal' => ProposalPresenter::proposal($proposal),
        ]);
    }

    /**
     * @return array{id: int, name: string, targetLaunchOn: string|null}
     */
    private function project(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'targetLaunchOn' => $project->target_launch_on?->toDateString(),
        ];
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
