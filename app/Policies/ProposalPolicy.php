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
     * Editing a draft: the studio.
     */
    public function update(User $user, Proposal $proposal): Response
    {
        return $this->adminOfWorkspace($user, $proposal->workspace_id);
    }
}
