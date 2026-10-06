<?php

namespace App\Models;

use App\Enums\ProjectPhase;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * A website project for one organization.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property ProjectPhase $phase
 * @property CarbonImmutable|null $target_launch_on
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['organization_id', 'name', 'description', 'phase', 'target_launch_on'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $attributes = [
        'phase' => 'scope',
    ];

    protected static function booted(): void
    {
        // Defence in depth: validation already restricts organization_id to
        // the workspace, but the model refuses a cross-workspace link too.
        static::saving(function (self $project): void {
            $organization = Organization::withoutGlobalScopes()->find($project->organization_id);

            // Outside a request (seeders, the sandbox), inherit the workspace.
            $project->workspace_id ??= $organization?->workspace_id;

            if ($organization === null || $organization->workspace_id !== $project->workspace_id) {
                throw new LogicException('A project must belong to an organization in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasOne<Launch, $this>
     */
    public function launch(): HasOne
    {
        return $this->hasOne(Launch::class);
    }

    /**
     * @return HasOne<BrandKit, $this>
     */
    public function brandKit(): HasOne
    {
        return $this->hasOne(BrandKit::class);
    }

    /**
     * Proofmark rounds, newest first.
     *
     * @return HasMany<ReviewRound, $this>
     */
    public function reviewRounds(): HasMany
    {
        return $this->hasMany(ReviewRound::class)->orderByDesc('number');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected function casts(): array
    {
        return [
            'phase' => ProjectPhase::class,
            'target_launch_on' => 'date',
            'archived_at' => 'datetime',
        ];
    }
}
