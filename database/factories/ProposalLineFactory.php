<?php

namespace Database\Factories;

use App\Enums\LineItemKind;
use App\Models\Proposal;
use App\Models\ProposalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalLine>
 */
class ProposalLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            // Always the proposal's workspace, even while a user is signed in.
            'workspace_id' => fn (array $attributes) => Proposal::withoutGlobalScopes()
                ->whereKey($attributes['proposal_id'])->firstOrFail()->workspace_id,
            'name' => 'Design and build',
            'kind' => LineItemKind::Fixed,
            'unit_price_cents' => 250000,
            'quantity' => 100,
        ];
    }
}
