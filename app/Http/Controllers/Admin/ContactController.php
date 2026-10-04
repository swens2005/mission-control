<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Add a client contact with a generated temporary password.
     *
     * This host can't send email, so the password is shown to the admin once
     * (as flash data, which is never stored in the page history) and stored
     * only as a hash. Email invitations are in the backlog.
     */
    public function store(ContactRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('addContact', $organization);

        $password = Str::password(16, symbols: false);

        $contact = new User([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $password,
        ]);
        $contact->role = Role::Client;
        $contact->organization_id = $organization->id;
        $contact->email_verified_at = now();
        $contact->save();

        Inertia::flash('newContact', [
            'name' => $contact->name,
            'email' => $contact->email,
            'temporaryPassword' => $password,
        ]);

        return to_route('admin.organizations.show', $organization);
    }
}
