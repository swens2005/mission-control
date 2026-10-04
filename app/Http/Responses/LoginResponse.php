<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Sends each user to their own portal after login (with or without 2FA).
 *
 * An "intended" URL from before login is honoured only inside the user's own
 * portal, so a client who followed an /admin link lands on /client, not 403.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $home = $user->portalHomeUrl();

        $intended = $request->session()->pull('url.intended');

        return redirect()->to(
            is_string($intended) && Str::startsWith($intended, $home) ? $intended : $home,
        );
    }
}
