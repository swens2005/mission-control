<?php

namespace Database\Factories;

use App\Enums\RoundStatus;
use App\Models\Project;
use App\Models\ReviewRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewRound>
 */
class ReviewRoundFactory extends Factory
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
            'number' => fn (array $attributes) => ReviewRound::withoutGlobalScopes()
                ->where('project_id', $attributes['project_id'])->max('number') + 1,
            'status' => RoundStatus::Draft,
        ];
    }

    public function inReview(): static
    {
        return $this->state(['status' => RoundStatus::InReview, 'sent_at' => now()]);
    }
}
