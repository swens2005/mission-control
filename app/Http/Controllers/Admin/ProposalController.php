<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Rules\Price;
use App\Support\Activity;
use App\Support\Money\Discount;
use App\Support\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Scope: starting a proposal, its title and introduction, and its
 * discount (story 27). Lines live in ProposalLineController.
 */
class ProposalController extends Controller
{
    public function store(Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        if (! $project->proposals()->exists()) {
            $proposal = new Proposal;
            $proposal->project_id = $project->id;
            $proposal->number = 1;
            $proposal->title = "Proposal: {$project->name}";
            $proposal->save();

            Activity::record('scope.proposal_started', $proposal, ['name' => $project->name, 'version' => 'v1']);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Proposal v1 started.']);
        }

        return to_route('admin.scope.show', $project);
    }

    /**
     * Sends the draft to the client: the version is frozen from now on.
     */
    public function send(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $proposal->load('lines');

        if ($proposal->lines->isEmpty()) {
            throw ValidationException::withMessages(['send' => 'Add at least one line before sending.']);
        }

        if ($proposal->discount()?->exceeds($proposal->totals()->subtotal)) {
            throw ValidationException::withMessages(['send' => 'The discount is more than the subtotal. Lower it before sending.']);
        }

        /** @var User $user */
        $user = $request->user();

        DB::transaction(fn () => $proposal->freeze($user->name));

        Activity::record('scope.proposal_sent', $proposal, [
            'name' => $proposal->project->name,
            'version' => "v{$proposal->number}",
            'total' => Money::format((int) $proposal->total_cents),
        ], visibleToClient: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proposal v{$proposal->number} sent to the client."]);

        return to_route('admin.scope.show', $proposal->project_id);
    }

    /**
     * "Make changes": the next version as a draft copy of this one. The
     * sent version stays exactly as it was.
     */
    public function revise(Proposal $proposal): RedirectResponse
    {
        Gate::authorize('revise', $proposal);

        $next = DB::transaction(function () use ($proposal): Proposal {
            $next = new Proposal;
            $next->project_id = $proposal->project_id;
            $next->number = $proposal->number + 1;
            $next->title = $proposal->title;
            $next->introduction = $proposal->introduction;
            $next->discount_type = $proposal->discount_type;
            $next->discount_value = $proposal->discount_value;
            $next->discount_label = $proposal->discount_label;
            $next->save();

            foreach ($proposal->lines as $line) {
                $copy = $line->replicate(['total_cents']);
                $copy->proposal_id = $next->id;
                $copy->save();
            }

            return $next;
        });

        Activity::record('scope.proposal_started', $next, ['name' => $proposal->project->name, 'version' => "v{$next->number}"]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Draft v{$next->number} started from v{$proposal->number}."]);

        return to_route('admin.scope.show', $proposal->project_id);
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'introduction' => ['nullable', 'string', 'max:5000'],
        ]);

        $proposal->title = trim($data['title']);
        $proposal->introduction = isset($data['introduction']) ? trim($data['introduction']) : null;
        $proposal->save();

        return to_route('admin.scope.show', $proposal->project_id);
    }

    public function discount(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $data = $request->validate([
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'required_unless:discount_type,none', 'string', 'max:20'],
            'discount_label' => ['nullable', 'string', 'max:60'],
        ], [
            'discount_value.required_unless' => 'Enter the discount.',
        ]);

        $type = DiscountType::from($data['discount_type']);
        $input = (string) ($data['discount_value'] ?? '');
        $subtotal = $proposal->totals()->subtotal;

        $value = match ($type) {
            DiscountType::None => 0,
            DiscountType::Percentage => Discount::parsePercentage($input),
            DiscountType::Fixed => Money::parse($input),
        };

        if ($type === DiscountType::Percentage && ($value === null || $value > 10000)) {
            throw ValidationException::withMessages(['discount_value' => 'Enter a percentage between 0 and 100, like 10 or 12.5.']);
        }

        if ($type === DiscountType::Fixed) {
            $request->validate(['discount_value' => [new Price]]);

            if ($value > $subtotal) {
                throw ValidationException::withMessages(['discount_value' => 'The discount can’t be more than the subtotal ('.Money::format($subtotal).').']);
            }
        }

        $proposal->discount_type = $type;
        $proposal->discount_value = (int) $value;
        $proposal->discount_label = $type === DiscountType::None || ! isset($data['discount_label']) ? null : trim($data['discount_label']);

        if ($proposal->isDirty()) {
            $proposal->save();

            Activity::record('scope.discount_changed', $proposal, [
                'name' => $proposal->project->name,
                'version' => "v{$proposal->number}",
                'discount' => $proposal->discountDescription() ?? 'none',
            ]);
        }

        return to_route('admin.scope.show', $proposal->project_id);
    }
}
