<?php

namespace App\Models;

use App\Enums\LineItemKind;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\ProposalLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line of a proposal: its own copy of a library item, or a custom line.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $proposal_id
 * @property int|null $line_item_id
 * @property string $name
 * @property string|null $description
 * @property LineItemKind $kind
 * @property int $unit_price_cents
 * @property int $quantity hundredths: 1.5 hours is 150
 * @property int|null $total_cents frozen when the proposal is sent
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Proposal $proposal
 */
class ProposalLine extends Model
{
    /** @use HasFactory<ProposalLineFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        // A sent version is frozen (story 28); policies refuse first, this
        // is the safety net.
        static::updating(function (self $line): void {
            if (Proposal::withoutGlobalScopes()->whereKey($line->proposal_id)->whereNotNull('sent_at')->exists()) {
                throw new LogicException('Lines of a sent proposal cannot be changed.');
            }
        });
        static::deleting(function (self $line): void {
            if (Proposal::withoutGlobalScopes()->whereKey($line->proposal_id)->whereNotNull('sent_at')->exists()) {
                throw new LogicException('Lines of a sent proposal cannot be removed.');
            }
        });

        static::saving(function (self $line): void {
            $proposal = Proposal::withoutGlobalScopes()->find($line->proposal_id);

            $line->workspace_id ??= $proposal?->workspace_id;

            if ($proposal === null || $proposal->workspace_id !== $line->workspace_id) {
                throw new LogicException('A proposal line must belong to a proposal in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    protected function casts(): array
    {
        return [
            'kind' => LineItemKind::class,
        ];
    }
}
