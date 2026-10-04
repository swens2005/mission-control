<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
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

    public function view(User $user, Project $project): Response
    {
        return $this->adminOfWorkspace($user, $project->workspace_id);
    }

    /**
     * A client contact may see their own organization's active projects.
     * Anything else is a 404, so other projects' ids don't leak.
     */
    public function viewAsClient(User $user, Project $project): Response
    {
        return $user->isClient()
            && $user->workspace_id === $project->workspace_id
            && $user->organization_id === $project->organization_id
            && ! $project->isArchived()
                ? Response::allow()
                : Response::denyAsNotFound();
    }

    public function update(User $user, Project $project): Response
    {
        return $this->adminOfWorkspace($user, $project->workspace_id);
    }
}
