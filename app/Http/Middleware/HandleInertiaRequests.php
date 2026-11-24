<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version: the build's manifest.
     *
     * Inertia's default uses the asset URL when one is set, as production
     * does for its subfolder. That URL never changes, so a tab left open
     * across a deploy was never told to reload and asked for files that
     * were gone (story 49).
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? (hash_file('xxh128', $manifest) ?: null) : parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user = $user instanceof User ? $user : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            // Which portal the signed-in user belongs to: 'admin' or 'client'.
            // The root template also puts it on <html data-portal>.
            'portal' => $user?->role->value,
            'workspace' => $user === null ? null : [
                'name' => $user->workspace->name,
                'isSandbox' => $user->workspace->is_sandbox,
                'expiresAt' => $user->workspace->expires_at?->toIso8601String(),
            ],
            // Autopilot speed in percent (ADR 0011); 0 in tests.
            'tourSpeed' => config()->integer('tour.speed'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
