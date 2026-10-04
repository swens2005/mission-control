<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps each role in its own portal: admins in /admin (Mission Control),
 * clients in /client (Launchpad). Usage: ->middleware('portal:admin').
 */
class EnsurePortal
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->role === Role::from($portal), 403);

        return $next($request);
    }
}
