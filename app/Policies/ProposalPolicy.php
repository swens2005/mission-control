<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class ProposalPolicy
{
    use AdminOfWorkspace;

    /**
     * Any version, read-only: the studio.
     */
    public function view(User $user, Proposal $proposal): Response
    {
        return $this->adminOfWorkspace($user, $proposal->workspace_id);
    }

    /**
     * Clients see their own projects' sent versions; drafts are a 404.
     */
    public function viewAsClient(User $user, Proposal $proposal): Response
    {
        $canSee = (new ProjectPolicy)->viewAsClient($user, $proposal->project);

        return $canSee->allowed() && $proposal->isSent()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Editing: the studio, while it's a draft. Sending freezes a version.
     */
    public function update(User $user, Proposal $proposal): Response
    {
        $admin = $this->adminOfWorkspace($user, $proposal->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        return $proposal->isSent()
            ? Response::deny('This version was sent and can’t change. Use “Make changes” for a new version.')
            : Response::allow();
    }

    /**
     * "Make changes": a new draft from the latest sent version, when there
     * isn't a draft already.
     */
    public function revise(User $user, Proposal $proposal): Response
    {
        $admin = $this->adminOfWorkspace($user, $proposal->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        $latest = $proposal->project->proposals()->first();

        return $proposal->isSent() && $latest?->is($proposal)
            ? Response::allow()
            : Response::deny('Only the latest sent version can get a new version.');
    }
}
