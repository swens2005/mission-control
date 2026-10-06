<?php

namespace App\Policies;

use App\Models\Design;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DesignPolicy
{
    /**
     * The image route serves both portals: the studio sees every design in
     * its workspace, a client only those in rounds sent to them.
     */
    public function view(User $user, Design $design): Response
    {
        $rounds = new ReviewRoundPolicy;

        return $user->isAdmin()
            ? $rounds->view($user, $design->round)
            : $rounds->viewAsClient($user, $design->round);
    }
}
