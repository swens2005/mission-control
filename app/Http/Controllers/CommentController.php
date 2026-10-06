<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Design;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Pinned comments on a design, from either portal (story 17).
 */
class CommentController extends Controller
{
    public function store(Request $request, Design $design): RedirectResponse
    {
        $round = $design->round;
        Gate::authorize('comment', $round);

        $validated = $request->validateWithBag('comment', [
            'x' => ['required', 'integer', 'between:0,'.Comment::MAX_POSITION],
            'y' => ['required', 'integer', 'between:0,'.Comment::MAX_POSITION],
            'body' => ['required', 'string', 'max:'.Comment::MAX_LENGTH],
        ], [
            'body.required' => 'Write what should change here.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $comment = new Comment;
        $comment->design_id = $design->id;
        $comment->author_id = $user->id;
        $comment->author_name = $user->name;
        $comment->author_role = $user->isAdmin() ? 'studio' : 'client';
        $comment->x = (int) $validated['x'];
        $comment->y = (int) $validated['y'];
        $comment->body = $validated['body'];
        $comment->save();

        Activity::record('proofmark.commented', $round, [
            'name' => $round->project->name,
            'round' => $round->label(),
            'design' => $design->title,
        ], visibleToClient: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comment pinned.']);

        return to_route($user->isAdmin() ? 'admin.proofmark.show' : 'client.proofmark.show', [
            $round->project_id,
            'round' => $round->number,
        ]);
    }
}
