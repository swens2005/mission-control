<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * An isolated copy of the studio: the real one, or a visitor's demo sandbox.
 *
 * @property int $id
 * @property string $name
 * @property bool $is_sandbox
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'is_sandbox', 'expires_at'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * The signed-in user's workspace, or null when nobody is signed in.
     *
     * Uses hasUser() so it never triggers the user lookup itself: that lookup
     * queries the (workspace-scoped) users table, which would recurse.
     */
    public static function currentId(): ?int
    {
        $user = Auth::hasUser() ? Auth::user() : null;

        return $user instanceof User ? $user->workspace_id : null;
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    protected function casts(): array
    {
        return [
            'is_sandbox' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }
}
