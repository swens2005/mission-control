<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Studio',
            'is_sandbox' => false,
            'expires_at' => null,
        ];
    }

    public function sandbox(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sandbox' => true,
            'expires_at' => now()->addDay(),
        ]);
    }
}
