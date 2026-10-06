<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A pinned comment on a design (story 17). The position is in hundredths
 * of a percent of the image, so it holds at any size.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $design_id
 * @property int|null $author_id
 * @property string $author_name
 * @property 'studio'|'client' $author_role
 * @property int $x
 * @property int $y
 * @property string $body
 * @property CarbonImmutable|null $resolved_at
 * @property int|null $resolved_by_id
 * @property string|null $resolved_by_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Design $design
 */
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use BelongsToWorkspace, HasFactory;

    public const MAX_POSITION = 10_000;

    public const MAX_LENGTH = 2000;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $comment): void {
            $design = Design::withoutGlobalScopes()->find($comment->design_id);

            $comment->workspace_id ??= $design?->workspace_id;

            if ($design === null || $design->workspace_id !== $comment->workspace_id) {
                throw new LogicException('A comment must belong to a design in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Design, $this>
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }
}
