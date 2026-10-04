<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\Response;

trait AdminOfWorkspace
{
    /**
     * Allow admins of the record's own workspace. Everyone else gets a 404
     * rather than a 403, so ids from other workspaces don't leak.
     */
    protected function adminOfWorkspace(User $user, int $workspaceId): Response
    {
        return $user->isAdmin() && $user->workspace_id === $workspaceId
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
