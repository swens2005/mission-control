<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates a real (non-sandbox) studio workspace and its first admin
 * (story 38, ADR 0012). Run once, by hand, from cPanel's Terminal:
 * `php artisan studio:create`. Asks for everything; the password is typed
 * twice, hidden, and never printed.
 */
#[Signature('studio:create')]
#[Description('Create a real studio workspace and its first admin')]
class CreateStudio extends Command
{
    use PasswordValidationRules;

    public function handle(): int
    {
        $input = [
            'studio' => trim((string) $this->ask('Studio name')),
            'name' => trim((string) $this->ask('Your name')),
            'email' => Str::lower(trim((string) $this->ask('Your email address'))),
            'password' => (string) $this->secret('Password (hidden)'),
            'password_confirmation' => (string) $this->secret('Password again'),
        ];

        $validator = Validator::make($input, [
            'studio' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255', 'unique:users,email',
                // Demo logins live on this domain; a real account must not.
                'not_regex:/@'.preg_quote(config()->string('demo.email_domain'), '/').'$/i',
            ],
            'password' => $this->passwordRules(),
        ], [
            'email.not_regex' => 'That domain is for demo sandboxes; use your own email address.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            $this->line('Nothing was created. Run the command again.');

            return self::FAILURE;
        }

        $admin = DB::transaction(function () use ($input): User {
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

        $this->info("Created \"{$input['studio']}\". Log in as {$admin->email}.");

        return self::SUCCESS;
    }
}
