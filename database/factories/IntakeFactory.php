<?php

namespace Database\Factories;

use App\Enums\IntakeBudget;
use App\Models\Intake;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intake>
 */
class IntakeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            // Always the project's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => Project::withoutGlobalScopes()
                ->whereKey($attributes['project_id'])->firstOrFail()->workspace_id,
            'requested_at' => now(),
        ];
    }

    /**
     * Filled in and sent by the client.
     */
    public function submitted(): static
    {
        return $this->state(fn () => [
            'goals' => 'More orders through the website.',
            'pages' => ['Home', 'Menu', 'Contact'],
            'features' => ['contact_form'],
            'budget' => IntakeBudget::From2500,
            'submitted_at' => now(),
            'submitted_by_name' => 'Anna de Vries',
        ]);
    }
}
