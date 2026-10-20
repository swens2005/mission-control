<?php

namespace App\Support\Scope;

use App\Enums\IntakeBudget;
use App\Enums\IntakeFeature;
use App\Models\Intake;

/**
 * The intake as plain arrays for Inertia (story 25).
 */
final class IntakePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function intake(Intake $intake): array
    {
        $features = array_values(array_filter(array_map(
            fn (string $key) => IntakeFeature::tryFrom($key),
            $intake->features ?? [],
        )));

        return [
            'id' => $intake->id,
            'submitted' => $intake->isSubmitted(),
            'requestedAt' => $intake->requested_at->toIso8601String(),
            'submittedAt' => $intake->submitted_at?->toIso8601String(),
            'submittedByName' => $intake->submitted_by_name,
            'goals' => $intake->goals,
            'pages' => $intake->pages ?? [],
            'features' => array_map(fn (IntakeFeature $feature) => $feature->value, $features),
            'featureLabels' => array_map(fn (IntakeFeature $feature) => $feature->label(), $features),
            'featuresOther' => $intake->features_other,
            'budget' => $intake->budget?->value,
            'budgetLabel' => $intake->budget?->label(),
            'deadlineOn' => $intake->deadline_on?->toDateString(),
            'deadlineReason' => $intake->deadline_reason,
        ];
    }

    /**
     * The choices on the form.
     *
     * @return array{features: list<array{value: string, label: string}>, budgets: list<array{value: string, label: string}>}
     */
    public static function options(): array
    {
        return [
            'features' => array_map(
                fn (IntakeFeature $feature) => ['value' => $feature->value, 'label' => $feature->label()],
                IntakeFeature::cases(),
            ),
            'budgets' => array_map(
                fn (IntakeBudget $budget) => ['value' => $budget->value, 'label' => $budget->label()],
                IntakeBudget::cases(),
            ),
        ];
    }
}
