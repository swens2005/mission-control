<?php

namespace Database\Factories;

use App\Enums\ProjectPhase;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            // Always the organization's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => Organization::withoutGlobalScopes()
                ->findOrFail($attributes['organization_id'])->workspace_id,
            'name' => fake()->city().' website',
            'description' => fake()->sentence(12),
            'phase' => ProjectPhase::Scope,
            'target_launch_on' => fake()->dateTimeBetween('+2 weeks', '+4 months'),
            'archived_at' => null,
        ];
    }

    public function phase(ProjectPhase $phase): static
    {
        return $this->state(fn (array $attributes) => [
            'phase' => $phase,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
