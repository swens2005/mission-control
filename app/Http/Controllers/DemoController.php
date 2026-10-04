<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Sandbox\SandboxFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The public demo: a sandbox per visitor (ADR 0004, story 08).
 */
class DemoController extends Controller
{
    /**
     * "Take the controls": create a sandbox and pre-fill the login form.
     * The visitor then really logs in.
     */
    public function store(Request $request, SandboxFactory $sandboxes): RedirectResponse
    {
        abort_unless(config()->boolean('demo.enabled'), 404);

        $active = Workspace::query()
            ->where('is_sandbox', true)
            ->where('expires_at', '>', now())
            ->count();

        if ($active >= config()->integer('demo.max_active')) {
            return to_route('login')->with('status', 'The demo is busy right now. Please try again in a little while.');
        }

        $request->session()->put('demo', $sandboxes->create()->credentials());

        return to_route('login');
    }

    /**
     * "View as client" / "View as studio": sign in as the sandbox's other
     * user. Only ever within the same sandbox.
     */
    public function switch(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->workspace->is_sandbox, 403);

        $target = User::query()
            ->where('workspace_id', $user->workspace_id)
            ->where('role', $user->isAdmin() ? Role::Client : Role::Admin)
            ->orderBy('id')
            ->first();

        abort_if($target === null, 404);

        Auth::login($target);
        $request->session()->regenerate();

        return redirect()->to($target->portalHomeUrl());
    }
}
