<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\DesignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * One screen in a review round: a title and a re-encoded image (ADR 0009).
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $review_round_id
 * @property string $title
 * @property int $position
 * @property 'upload'|'demo' $source
 * @property string $path
 * @property string|null $original_name
 * @property string $mime
 * @property int $width
 * @property int $height
 * @property int $bytes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ReviewRound $round
 */
class Design extends Model
{
    /** @use HasFactory<DesignFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $design): void {
            $round = ReviewRound::withoutGlobalScopes()->find($design->review_round_id);

            $design->workspace_id ??= $round?->workspace_id;

            if ($round === null || $round->workspace_id !== $design->workspace_id) {
                throw new LogicException('A design must belong to a round in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<ReviewRound, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(ReviewRound::class, 'review_round_id');
    }

    /**
     * Pinned comments, oldest first; their order gives the pin numbers.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('id');
    }
}
