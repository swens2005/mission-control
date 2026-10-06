<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The studio resolves a pinned comment, or reopens it (story 18).
 */
class CommentResolutionController extends Controller
{
    public function store(Request $request, Comment $comment): RedirectResponse
    {
        Gate::authorize('resolve', $comment);

        /** @var User $user */
        $user = $request->user();

        if (! $comment->isResolved()) {
            $comment->resolved_at = now();
            $comment->resolved_by_id = $user->id;
            $comment->resolved_by_name = $user->name;
            $comment->save();

            $this->record('proofmark.comment_resolved', $comment);
        }

        return $this->back($comment);
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('resolve', $comment);

        if ($comment->isResolved()) {
            $comment->resolved_at = null;
            $comment->resolved_by_id = null;
            $comment->resolved_by_name = null;
            $comment->save();

            $this->record('proofmark.comment_reopened', $comment);
        }

        return $this->back($comment);
    }

    private function record(string $event, Comment $comment): void
    {
        $design = $comment->design;
        $round = $design->round;

        Activity::record($event, $round, [
            'name' => $round->project->name,
            'round' => $round->label(),
            'design' => $design->title,
            // The pin number people see: its place among the design's comments.
            'number' => $design->comments()->where('id', '<=', $comment->id)->count(),
        ], visibleToClient: true);
    }

    private function back(Comment $comment): RedirectResponse
    {
        $round = $comment->design->round;

        return to_route('admin.proofmark.show', [$round->project_id, 'round' => $round->number]);
    }
}
