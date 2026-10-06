<?php

namespace App\Policies;

use App\Enums\RoundStatus;
use App\Models\Comment;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    use AdminOfWorkspace;

    /**
     * The studio works through comments while the round is in review;
     * approved and superseded rounds are kept as they were.
     */
    public function resolve(User $user, Comment $comment): Response
    {
        $admin = $this->adminOfWorkspace($user, $comment->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        $round = $comment->design->round;

        return $round->status === RoundStatus::InReview
            ? Response::allow()
            : Response::deny("{$round->label()} is locked: it is no longer in review.");
    }
}
