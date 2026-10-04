<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use LogicException;

/**
 * @property int $id
 * @property int $workspace_id
 * @property Role $role
 * @property int|null $organization_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToWorkspace, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $attributes = [
        'role' => 'client',
    ];

    protected static function booted(): void
    {
        // Clients always belong to an organization in their own workspace;
        // admins never belong to one.
        static::saving(function (self $user): void {
            if ($user->role === Role::Admin) {
                if ($user->organization_id !== null) {
                    throw new LogicException('An admin cannot belong to a client organization.');
                }

                return;
            }

            $organization = Organization::withoutGlobalScopes()->find($user->organization_id);

            if ($organization === null) {
                throw new LogicException('A client must belong to an organization.');
            }

            $user->workspace_id ??= $organization->workspace_id;

            if ($organization->workspace_id !== $user->workspace_id) {
                throw new LogicException('A client must belong to an organization in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isClient(): bool
    {
        return $this->role === Role::Client;
    }

    /**
     * Home of this user's portal: Mission Control for admins, Launchpad for
     * clients.
     */
    public function portalHomeUrl(): string
    {
        return $this->isAdmin() ? route('admin.dashboard') : route('client.home');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
