<?php

namespace Database\Factories;

use App\Models\Launch;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Launch>
 */
class LaunchFactory extends Factory
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
            'url' => 'https://'.fake()->domainWord().'.example',
        ];
    }
}
