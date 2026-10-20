<?php

namespace App\Policies;

use App\Models\Intake;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class IntakePolicy
{
    use AdminOfWorkspace;

    /**
     * Clients see their own projects' intakes; otherwise a 404.
     */
    public function viewAsClient(User $user, Intake $intake): Response
    {
        return (new ProjectPolicy)->viewAsClient($user, $intake->project);
    }

    /**
     * Any contact of the organization fills it in, until it's sent.
     */
    public function fill(User $user, Intake $intake): Response
    {
        $canSee = $this->viewAsClient($user, $intake);

        if ($canSee->denied()) {
            return $canSee;
        }

        return $intake->isSubmitted()
            ? Response::deny('This intake was already sent to the studio.')
            : Response::allow();
    }

    /**
     * Sending it back to the client for changes: the studio.
     */
    public function reopen(User $user, Intake $intake): Response
    {
        return $this->adminOfWorkspace($user, $intake->workspace_id);
    }
}
