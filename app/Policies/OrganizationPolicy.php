<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    use AdminOfWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Organization $organization): Response
    {
        return $this->adminOfWorkspace($user, $organization->workspace_id);
    }

    public function update(User $user, Organization $organization): Response
    {
        return $this->adminOfWorkspace($user, $organization->workspace_id);
    }

    /**
     * Adding client contacts (users with the client role).
     */
    public function addContact(User $user, Organization $organization): Response
    {
        $access = $this->adminOfWorkspace($user, $organization->workspace_id);

        if ($access->allowed() && $organization->isArchived()) {
            return Response::deny('Restore this client before adding contacts.');
        }

        return $access;
    }
}
