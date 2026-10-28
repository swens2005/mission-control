<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Watch it fly" (story 31, ADR 0011): the page that offers the tours, and
 * the clean start every tour begins with.
 */
class AutopilotController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        $response = Inertia::render('public/autopilot', [
            'demoEnabled' => config()->boolean('demo.enabled'),
            // A signed-in visitor is told a tour starts a new demo.
            'signedInAs' => $user instanceof User ? $user->name : null,
        ])->toResponse($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    /**
     * Logs out of whatever demo the visitor was in and opens the login
     * page, where the tour clicks "Take the controls" for a new sandbox.
     * A POST with the CSRF token, so other sites can't log anyone out.
     */
    public function fresh(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return to_route('login');
    }
}
