<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\BelongsToWorkspace;
use App\Support\Money\Discount;
use App\Support\Money\Money;
use App\Support\Money\ProposalTotals;
use Carbon\CarbonImmutable;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Scope: one version of a project's proposal (stories 27 to 29).
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property int $number
 * @property string $title
 * @property string|null $introduction
 * @property DiscountType $discount_type
 * @property int $discount_value basis points or cents, by type
 * @property string|null $discount_label
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 * @property-read Collection<int, ProposalLine> $lines
 */
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discount_type' => 'none',
        'discount_value' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $proposal): void {
            $project = Project::withoutGlobalScopes()->find($proposal->project_id);

            $proposal->workspace_id ??= $project?->workspace_id;

            if ($project === null || $project->workspace_id !== $proposal->workspace_id) {
                throw new LogicException('A proposal must belong to a project in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<ProposalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ProposalLine::class)->orderBy('position')->orderBy('id');
    }

    public function discount(): ?Discount
    {
        return match ($this->discount_type) {
            DiscountType::None => null,
            DiscountType::Percentage => Discount::percentage($this->discount_value),
            DiscountType::Fixed => Discount::fixed($this->discount_value),
        };
    }

    /**
     * "10%" or "€250.00"; null without a discount.
     */
    public function discountDescription(): ?string
    {
        return match ($this->discount_type) {
            DiscountType::None => null,
            DiscountType::Percentage => Discount::formatPercentage($this->discount_value),
            DiscountType::Fixed => Money::format($this->discount_value),
        };
    }

    /**
     * The sums, worked out from the lines and the discount (ADR 0010).
     */
    public function totals(): ProposalTotals
    {
        return ProposalTotals::calculate(
            array_values($this->lines->map(fn (ProposalLine $line) => [
                'unitCents' => $line->unit_price_cents,
                'quantity' => $line->quantity,
            ])->all()),
            $this->discount(),
        );
    }

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
        ];
    }
}
