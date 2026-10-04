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

    public function update(User $user, Project $project): Response
    {
        return $this->adminOfWorkspace($user, $project->workspace_id);
    }
}
