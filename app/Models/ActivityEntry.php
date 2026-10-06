<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * One line in the shared audit trail. Append-only: entries are never updated
 * or deleted, except when their whole workspace is (by the database cascade).
 * Write them with App\Support\Activity::record().
 *
 * @property int $id
 * @property int $workspace_id
 * @property int|null $actor_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $project_id
 * @property string $event
 * @property array<string, mixed>|null $properties
 * @property bool $visible_to_client
 * @property CarbonImmutable $created_at
 */
class ActivityEntry extends Model
{
    use BelongsToWorkspace;

    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    /** Nothing is mass assignable: Activity::record() sets every field. */
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Activity entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Activity entries cannot be deleted.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisibleToClient(Builder $query): void
    {
        $query->where('visible_to_client', true);
    }

    /**
     * A plain-English sentence, without the actor ("created project X").
     * Uses the names stored at the time, so renames don't rewrite history.
     */
    public function description(): string
    {
        $p = $this->properties ?? [];
        $name = (string) ($p['name'] ?? 'something');

        return match ($this->event) {
            'organization.created' => "added client {$name}",
            'organization.updated' => "updated client {$name}",
            'organization.archived' => "archived client {$name}",
            'organization.restored' => "restored client {$name}",
            'contact.added' => "added {$name} as a contact for ".($p['organization'] ?? 'a client'),
            'project.created' => "started project {$name}",
            'project.updated' => "updated project {$name}",
            'project.phase_changed' => "moved {$name} from {$p['from']} to {$p['to']}",
            'project.archived' => "archived project {$name}",
            'project.restored' => "restored project {$name}",
            'launch.created' => "prepared the launch of {$name}",
            'launch.updated' => "changed the site URL of {$name} to ".($p['url'] ?? 'a new address'),
            'checklist.checked' => "ticked {$name} on the launch checklist",
            'checklist.unchecked' => "unticked {$name} on the launch checklist",
            'checklist.item_added' => "added {$name} to the launch checklist",
            'checklist.item_removed' => "removed {$name} from the launch checklist",
            default => str_replace(['.', '_'], ' ', $this->event)." {$name}",
        };
    }

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'visible_to_client' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
