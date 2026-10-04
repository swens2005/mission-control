<?php

namespace Database\Seeders;

use App\Support\Sandbox\SandboxFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Local development: one demo studio, built exactly like a visitor's
     * sandbox, with its logins printed once.
     */
    public function run(SandboxFactory $sandboxes): void
    {
        $sandbox = $sandboxes->create();
        $credentials = $sandbox->credentials();

        $this->command->info('Demo studio created (expires in 24 hours; run db:seed again for a new one).');
        $this->command->table(['Portal', 'Email', 'Password'], [
            ['Mission Control (admin)', $credentials['admin']['email'], $credentials['admin']['password']],
            ['Launchpad (client)', $credentials['client']['email'], $credentials['client']['password']],
        ]);
    }
}
