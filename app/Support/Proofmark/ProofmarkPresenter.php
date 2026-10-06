<?php

namespace App\Support\Proofmark;

use App\Enums\RoundStatus;
use App\Models\Comment;
use App\Models\Design;
use App\Models\ReviewRound;
use Illuminate\Support\Collection;

/**
 * Proofmark's rounds and designs as plain arrays for Inertia.
 */
final class ProofmarkPresenter
{
    /**
     * The round switcher: every round, newest first.
     *
     * @param  Collection<int, ReviewRound>  $rounds
     * @return array<int, array<string, mixed>>
     */
    public static function rounds(Collection $rounds): array
    {
        return $rounds->map(fn (ReviewRound $round) => [
            'id' => $round->id,
            'number' => $round->number,
            'label' => $round->label(),
            'status' => $round->status->value,
            'statusLabel' => $round->status->label(),
            'designCount' => $round->designs->count(),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function round(ReviewRound $round): array
    {
        return [
            'id' => $round->id,
            'number' => $round->number,
            'label' => $round->label(),
            'status' => $round->status->value,
            'statusLabel' => $round->status->label(),
            'sentAt' => $round->sent_at?->toIso8601String(),
            'approvedAt' => $round->approved_at?->toIso8601String(),
            'approvedByName' => $round->approved_by_name,
            'canComment' => $round->status === RoundStatus::InReview,
            'designs' => $round->designs->map(fn (Design $design) => self::design($design))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function design(Design $design): array
    {
        return [
            'id' => $design->id,
            'title' => $design->title,
            'width' => $design->width,
            'height' => $design->height,
            'bytes' => $design->bytes,
            'size' => DesignFiles::humanSize($design->bytes),
            'imageUrl' => route('designs.image', $design),
            'comments' => $design->comments->values()->map(fn (Comment $comment, int $i) => [
                'id' => $comment->id,
                'number' => $i + 1,
                'x' => $comment->x,
                'y' => $comment->y,
                'body' => $comment->body,
                'authorName' => $comment->author_name,
                'authorRole' => $comment->author_role,
                'createdAt' => $comment->created_at?->toIso8601String(),
                'resolved' => $comment->isResolved(),
                'resolvedByName' => $comment->resolved_by_name,
            ])->all(),
        ];
    }
}
