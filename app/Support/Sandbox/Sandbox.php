<?php

namespace App\Support\Sandbox;

use App\Models\User;
use App\Models\Workspace;

/**
 * A freshly created sandbox and its one-time plain-text credentials.
 */
final readonly class Sandbox
{
    public function __construct(
        public Workspace $workspace,
        public User $admin,
        public string $adminPassword,
        public User $client,
        public string $clientPassword,
    ) {}

    /**
     * What the login page shows the visitor (story 08).
     *
     * @return array{admin: array{email: string, password: string}, client: array{email: string, password: string}, expiresAt: string|null}
     */
    public function credentials(): array
    {
        return [
            'admin' => ['email' => $this->admin->email, 'password' => $this->adminPassword],
            'client' => ['email' => $this->client->email, 'password' => $this->clientPassword],
            'expiresAt' => $this->workspace->expires_at?->toIso8601String(),
        ];
    }
}
