<?php

namespace App\Policies;

use App\Enums\RoundStatus;
use App\Models\ReviewRound;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class ReviewRoundPolicy
{
    use AdminOfWorkspace;

    public function view(User $user, ReviewRound $round): Response
    {
        return $this->adminOfWorkspace($user, $round->workspace_id);
    }

    /**
     * Designs can change only while the round is a draft. Once sent, the
     * client is reviewing exactly this set; changes mean a new round.
     */
    public function update(User $user, ReviewRound $round): Response
    {
        $admin = $this->adminOfWorkspace($user, $round->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        return $round->isDraft()
            ? Response::allow()
            : Response::deny("{$round->label()} has been sent to the client. Start a new round to change the designs.");
    }

    /**
     * Clients see their own projects' rounds once sent; drafts are a 404.
     */
    public function viewAsClient(User $user, ReviewRound $round): Response
    {
        $canSee = (new ProjectPolicy)->viewAsClient($user, $round->project);

        if ($canSee->denied() || $round->isDraft()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    /**
     * Any contact of the organization approves the round in review, once.
     */
    public function approve(User $user, ReviewRound $round): Response
    {
        $canSee = $this->viewAsClient($user, $round);

        if ($canSee->denied()) {
            return $canSee;
        }

        return $round->status === RoundStatus::InReview
            ? Response::allow()
            : Response::deny("{$round->label()} is not waiting for approval.");
    }

    /**
     * Both sides can pin comments, but only on the round in review: a draft
     * isn't shared yet, and superseded or approved rounds are history.
     */
    public function comment(User $user, ReviewRound $round): Response
    {
        $canSee = $user->isAdmin() ? $this->view($user, $round) : $this->viewAsClient($user, $round);

        if ($canSee->denied()) {
            return $canSee;
        }

        return $round->status === RoundStatus::InReview
            ? Response::allow()
            : Response::deny("{$round->label()} is not open for comments.");
    }
}
