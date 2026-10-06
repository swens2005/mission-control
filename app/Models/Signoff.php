<?php

namespace App\Models;

use App\Enums\ChecklistOwner;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A go/no-go signature by the studio or the client. Never deleted: when the
 * board stops being clear it is voided, with the reason, and stays in the
 * record.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $launch_id
 * @property int|null $user_id
 * @property ChecklistOwner $role
 * @property string $name_typed
 * @property string|null $ip
 * @property CarbonImmutable $signed_at
 * @property CarbonImmutable|null $voided_at
 * @property string|null $void_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Signoff extends Model
{
    use BelongsToWorkspace;

    protected $guarded = ['*'];

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    protected function casts(): array
    {
        return [
            'role' => ChecklistOwner::class,
            'signed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }
}
