<?php

namespace App\Console\Commands;

use App\Support\Studio\StudioCreator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates a real (non-sandbox) studio workspace and its first admin
 * (story 38, ADR 0012), for hosts with shell access. Asks for everything;
 * the password is typed twice, hidden, and never printed. Without shell
 * access, use the one-time setup page instead (story 41, ADR 0013).
 */
#[Signature('studio:create')]
#[Description('Create a real studio workspace and its first admin')]
class CreateStudio extends Command
{
    public function handle(StudioCreator $creator): int
    {
        $input = [
            'studio' => trim((string) $this->ask('Studio name')),
            'name' => trim((string) $this->ask('Your name')),
            'email' => Str::lower(trim((string) $this->ask('Your email address'))),
            'password' => (string) $this->secret('Password (hidden)'),
            'password_confirmation' => (string) $this->secret('Password again'),
        ];

        $validator = Validator::make($input, $creator->rules(), $creator->messages());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            $this->line('Nothing was created. Run the command again.');

            return self::FAILURE;
        }

        /** @var array{studio: string, name: string, email: string, password: string} $valid */
        $valid = $validator->validated();
        $admin = $creator->create($valid);

        $this->info("Created \"{$valid['studio']}\". Log in as {$admin->email}.");

        return self::SUCCESS;
    }
}
