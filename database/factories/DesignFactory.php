<?php

namespace Database\Factories;

use App\Models\Design;
use App\Models\ReviewRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A design record only; tests that need the image upload a real one.
 *
 * @extends Factory<Design>
 */
class DesignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_round_id' => ReviewRound::factory(),
            // Always the round's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => ReviewRound::withoutGlobalScopes()
                ->whereKey($attributes['review_round_id'])->firstOrFail()->workspace_id,
            'title' => fake()->randomElement(['Home', 'Order page', 'Contact']).', desktop',
            'position' => 0,
            'source' => 'upload',
            'path' => fn (array $attributes) => 'proofmark/'.$attributes['workspace_id'].'/'.fake()->uuid().'.png',
            'original_name' => 'design.png',
            'mime' => 'image/png',
            'width' => 1440,
            'height' => 900,
            'bytes' => 1000,
        ];
    }
}
