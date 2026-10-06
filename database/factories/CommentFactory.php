<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Design;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'design_id' => Design::factory(),
            // Always the design's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => Design::withoutGlobalScopes()
                ->whereKey($attributes['design_id'])->firstOrFail()->workspace_id,
            'author_name' => fake()->name(),
            'author_role' => 'client',
            'x' => fake()->numberBetween(0, Comment::MAX_POSITION),
            'y' => fake()->numberBetween(0, Comment::MAX_POSITION),
            'body' => fake()->sentence(),
        ];
    }
}
