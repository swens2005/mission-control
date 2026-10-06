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
