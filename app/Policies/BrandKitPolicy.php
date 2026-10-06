<?php

namespace App\Policies;

use App\Models\BrandKit;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class BrandKitPolicy
{
    use AdminOfWorkspace;

    public function view(User $user, BrandKit $kit): Response
    {
        return $this->adminOfWorkspace($user, $kit->workspace_id);
    }

    /**
     * Clients see their own projects' kits once shared; otherwise a 404.
     */
    public function viewAsClient(User $user, BrandKit $kit): Response
    {
        $canSee = (new ProjectPolicy)->viewAsClient($user, $kit->project);

        return $canSee->allowed() && $kit->shared_at !== null
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Any contact of the organization approves a shared kit, once.
     */
    public function approve(User $user, BrandKit $kit): Response
    {
        $canSee = $this->viewAsClient($user, $kit);

        if ($canSee->denied()) {
            return $canSee;
        }

        return $kit->isApproved()
            ? Response::deny('This brand kit is already approved.')
            : Response::allow();
    }

    /**
     * Sharing settings and revisions: the studio, whether or not approved.
     */
    public function share(User $user, BrandKit $kit): Response
    {
        return $this->adminOfWorkspace($user, $kit->workspace_id);
    }

    /**
     * An approved kit is locked; "Start a revision" (story 24) unlocks it.
     */
    public function update(User $user, BrandKit $kit): Response
    {
        $admin = $this->adminOfWorkspace($user, $kit->workspace_id);

        if ($admin->denied()) {
            return $admin;
        }

        return $kit->isApproved()
            ? Response::deny('The client approved this kit. Start a revision to change it.')
            : Response::allow();
    }
}
