<?php

namespace Database\Factories;

use App\Enums\ColorRole;
use App\Models\BrandKit;
use App\Models\Color;
use App\Support\Color\Oklch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Color>
 */
class ColorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_kit_id' => BrandKit::factory(),
            // Always the kit's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => BrandKit::withoutGlobalScopes()
                ->whereKey($attributes['brand_kit_id'])->firstOrFail()->workspace_id,
            'name' => fake()->unique()->colorName(),
            'role' => ColorRole::Text,
            'position' => 0,
            'hex' => '#10233a',
            'lightness' => fn (array $attributes) => Oklch::fromHex($attributes['hex'])->lightness,
            'chroma' => fn (array $attributes) => Oklch::fromHex($attributes['hex'])->chroma,
            'hue' => fn (array $attributes) => Oklch::fromHex($attributes['hex'])->hue,
        ];
    }

    public function hex(string $hex, ColorRole $role = ColorRole::Text): static
    {
        return $this->state(['hex' => $hex, 'role' => $role]);
    }
}
