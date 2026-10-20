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
     * Editing and sending: the studio, while it's a draft and the client
     * hasn't accepted another version.
     */
    public function update(User $user, Proposal $proposal): Response
    {
        $admin = $this->adminOfWorkspace($user, $proposal->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        if ($proposal->isSent()) {
            return Response::deny('This version was sent and can’t change. Use “Make changes” for a new version.');
        }

        return $this->projectAccepted($proposal)
            ? Response::deny('The client already accepted a version of this proposal.')
            : Response::allow();
    }

    /**
     * "Make changes": a new draft from the latest sent version, when there
     * isn't a draft already and nothing was accepted.
     */
    public function revise(User $user, Proposal $proposal): Response
    {
        $admin = $this->adminOfWorkspace($user, $proposal->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        if ($this->projectAccepted($proposal)) {
            return Response::deny('The client accepted this proposal; it can’t change any more.');
        }

        $latest = $proposal->project->proposals()->first();

        return $proposal->isSent() && $latest?->is($proposal)
            ? Response::allow()
            : Response::deny('Only the latest sent version can get a new version.');
    }

    /**
     * Accepting or asking for changes: a contact of the organization, on
     * the latest sent version, once.
     */
    public function respond(User $user, Proposal $proposal): Response
    {
        $canSee = $this->viewAsClient($user, $proposal);

        if ($canSee->denied()) {
            return $canSee;
        }

        $latestSent = $proposal->project->proposals()->whereNotNull('sent_at')->first();

        if (! $latestSent?->is($proposal)) {
            return Response::deny('A newer version of this proposal was sent.');
        }

        return $proposal->awaitsClient()
            ? Response::allow()
            : Response::deny('You already answered this version.');
    }

    private function projectAccepted(Proposal $proposal): bool
    {
        return $proposal->project->proposals()->whereNotNull('accepted_at')->exists();
    }
}
