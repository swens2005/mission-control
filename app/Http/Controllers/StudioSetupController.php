<?php

namespace App\Http\Controllers;

use App\Support\Studio\StudioCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The one-time setup page for the first real studio (story 41, ADR 0013):
 * for a host without shell access, where `studio:create` can't run.
 *
 * Open only while a token fingerprint is configured and no real studio
 * exists; otherwise 404, so it doesn't advertise itself.
 */
class StudioSetupController extends Controller
{
    public function show(): Response
    {
        abort_unless(self::open(), 404);

        return Inertia::render('auth/studio-setup');
    }

    public function store(Request $request, StudioCreator $creator): RedirectResponse
    {
        abort_unless(self::open(), 404);

        $token = $request->string('token')->toString();

        // Checked first and on its own: a wrong token learns nothing about
        // the other fields, and nothing is created.
        if ($token === '' || ! hash_equals(config()->string('studio.setup_token_sha256'), hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'token' => 'That setup token isn’t right.',
            ]);
        }

        $input = [
            'studio' => trim($request->string('studio')->toString()),
            'name' => trim($request->string('name')->toString()),
            'email' => Str::lower(trim($request->string('email')->toString())),
            'password' => $request->string('password')->toString(),
            'password_confirmation' => $request->string('password_confirmation')->toString(),
        ];

        /** @var array{studio: string, name: string, email: string, password: string} $valid */
        $valid = Validator::make($input, $creator->rules(), $creator->messages())->validate();

        $admin = $creator->create($valid);

        Auth::login($admin);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Welcome to {$valid['studio']}. Setup is now closed."]);

        return to_route('admin.dashboard');
    }

    private static function open(): bool
    {
        return preg_match('/^[0-9a-f]{64}$/', config()->string('studio.setup_token_sha256')) === 1
            && ! StudioCreator::realStudioExists();
    }
}
