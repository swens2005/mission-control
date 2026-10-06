<?php

namespace Database\Factories;

use App\Enums\ChecklistOwner;
use App\Models\ChecklistItem;
use App\Models\Launch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'launch_id' => Launch::factory(),
            // Always the launch's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => Launch::withoutGlobalScopes()
                ->whereKey($attributes['launch_id'])->firstOrFail()->workspace_id,
            'label' => fake()->sentence(3),
            'hint' => null,
            'owner' => ChecklistOwner::Studio,
            'position' => 0,
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => ['owner' => ChecklistOwner::Client]);
    }

    public function checked(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => now(),
            'checked_by_name' => 'Someone',
        ]);
    }
}
