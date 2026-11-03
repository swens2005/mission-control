<?php

namespace App\Support\Studio;

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Creates a real (non-sandbox) studio and its first admin. Shared by
 * `studio:create` (story 38) and the one-time setup page (story 41,
 * ADR 0013), so both apply the same rules.
 */
final class StudioCreator
{
    use PasswordValidationRules;

    /**
     * @return array<string, array<int, Password|ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'studio' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255', 'unique:users,email',
                // Demo logins live on this domain; a real account must not.
                'not_regex:/@'.preg_quote(config()->string('demo.email_domain'), '/').'$/i',
            ],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.not_regex' => 'That domain is for demo sandboxes; use your own email address.',
        ];
    }

    /**
     * True once any real studio exists; the setup page closes for good then.
     */
    public static function realStudioExists(): bool
    {
        return Workspace::query()->where('is_sandbox', false)->exists();
    }

    /**
     * @param  array{studio: string, name: string, email: string, password: string}  $input  validated
     */
    public function create(array $input): User
    {
        return DB::transaction(function () use ($input): User {
            $workspace = Workspace::create([
                'name' => $input['studio'],
                'is_sandbox' => false,
                'expires_at' => null,
            ]);

            $admin = new User([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
            $admin->workspace_id = $workspace->id;
            $admin->role = Role::Admin;
            $admin->email_verified_at = now();
            $admin->save();

            return $admin;
        });
    }
}
