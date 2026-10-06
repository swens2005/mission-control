<?php

namespace App\Policies;

use App\Enums\ChecklistOwner;
use App\Models\ChecklistItem;
use App\Models\User;
use App\Policies\Concerns\AdminOfWorkspace;
use Illuminate\Auth\Access\Response;

class ChecklistItemPolicy
{
    use AdminOfWorkspace;

    public function update(User $user, ChecklistItem $item): Response
    {
        return $this->adminOfWorkspace($user, $item->workspace_id);
    }

    /**
     * Clients may tick only their own items, on their own projects. Another
     * organization's item is a 404; a studio item they can see is a 403.
     */
    public function checkAsClient(User $user, ChecklistItem $item): Response
    {
        $canSee = (new ProjectPolicy)->viewAsClient($user, $item->launch->project);

        if ($canSee->denied()) {
            return $canSee;
        }

        return $item->owner === ChecklistOwner::Client
            ? Response::allow()
            : Response::deny('Only the studio can tick this item.');
    }
}
