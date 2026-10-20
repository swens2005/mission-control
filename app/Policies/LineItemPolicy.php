<?php

namespace App\Policies;

use App\Models\LineItem;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

/**
 * The price list is the studio's own: clients never see it.
 */
class LineItemPolicy
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

    public function update(User $user, LineItem $item): Response
    {
        return $this->adminOfWorkspace($user, $item->workspace_id);
    }
}
