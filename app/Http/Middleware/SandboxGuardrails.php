<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps demo sandboxes demo-shaped (story 08):
 *  - a sandbox that has expired logs its user out;
 *  - sandbox users can't change their email or password, turn on two-factor
 *    authentication, or delete their account (403). The UI hides these too.
 */
class SandboxGuardrails
{
    /** Routes that are off-limits to sandbox users. */
    private const BLOCKED_ROUTES = [
        'user-password.update',
        'profile.destroy',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.regenerate-recovery-codes',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->workspace->is_sandbox) {
            return $next($request);
        }

        if ($user->workspace->expires_at?->isPast()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->with('status', 'Your demo has ended. Start a new one any time.');
        }

        $changesEmail = $request->routeIs('profile.update')
            && $request->filled('email')
            && strtolower((string) $request->input('email')) !== $user->email;

        abort_if($changesEmail || $request->routeIs(self::BLOCKED_ROUTES), 403, 'Not available in the demo.');

        return $next($request);
    }
}
