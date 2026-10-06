<?php

namespace App\Models;

use App\Enums\RoundStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewRoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * One Proofmark round (v1, v2, …): the set of designs the client reviews
 * together.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property int $number
 * @property RoundStatus $status
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by_id
 * @property string|null $approved_by_name
 * @property string|null $approved_ip
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 */
class ReviewRound extends Model
{
    /** @use HasFactory<ReviewRoundFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $round): void {
            $project = Project::withoutGlobalScopes()->find($round->project_id);

            // Outside a request (the sandbox), inherit the workspace.
            $round->workspace_id ??= $project?->workspace_id;

            if ($project === null || $project->workspace_id !== $round->workspace_id) {
                throw new LogicException('A review round must belong to a project in its own workspace.');
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
     * @return HasMany<Design, $this>
     */
    public function designs(): HasMany
    {
        return $this->hasMany(Design::class)->orderBy('position')->orderBy('id');
    }

    public function isDraft(): bool
    {
        return $this->status === RoundStatus::Draft;
    }

    public function label(): string
    {
        return "v{$this->number}";
    }

    protected function casts(): array
    {
        return [
            'status' => RoundStatus::class,
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
