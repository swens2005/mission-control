<?php

namespace Database\Factories;

use App\Enums\LineItemKind;
use App\Models\LineItem;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LineItem>
 */
class LineItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Design and build',
            'kind' => LineItemKind::Fixed,
            'unit_price_cents' => 250000,
            'default_quantity' => 100,
        ];
    }

    public function hourly(int $rateCents = 8500, int $hours = 100): static
    {
        return $this->state(fn () => [
            'name' => 'Content work',
            'kind' => LineItemKind::Hourly,
            'unit_price_cents' => $rateCents,
            'default_quantity' => $hours,
        ]);
    }
}
