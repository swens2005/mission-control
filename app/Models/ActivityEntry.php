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
            'launch.checked' => "ran the launch checks for {$name}: ".($p['summary'] ?? 'done'),
            'check.waived' => "waived the {$name} check: ".($p['reason'] ?? ''),
            'check.unwaived' => "withdrew the waiver for the {$name} check",
            'launch.signed' => "signed off the launch of {$name} for the ".strtolower((string) ($p['role'] ?? 'team')),
            'launch.signoffs_voided' => "cancelled the sign-offs for {$name}: ".($p['reason'] ?? 'the board changed'),
            'proofmark.round_created' => 'started design round '.($p['round'] ?? '')." of {$name}",
            'proofmark.design_added' => 'added the design '.($p['design'] ?? '').' to round '.($p['round'] ?? '')." of {$name}",
            'proofmark.design_renamed' => 'renamed the design '.($p['from'] ?? '').' to '.($p['design'] ?? '').' in round '.($p['round'] ?? '')." of {$name}",
            'proofmark.round_sent' => 'sent design round '.($p['round'] ?? '')." of {$name} for review".(isset($p['replaces']) ? ", replacing {$p['replaces']}" : ''),
            'proofmark.commented' => 'commented on '.($p['design'] ?? 'a design').' in round '.($p['round'] ?? '')." of {$name}",
            'proofmark.comment_resolved' => 'resolved comment '.($p['number'] ?? '').' on '.($p['design'] ?? 'a design')." of {$name}",
            'proofmark.comment_reopened' => 'reopened comment '.($p['number'] ?? '').' on '.($p['design'] ?? 'a design')." of {$name}",
            'proofmark.round_approved' => 'approved design round '.($p['round'] ?? '')." of {$name}",
            'palette.kit_created' => "started the brand kit of {$name}",
            'palette.color_added' => 'added the color '.($p['color'] ?? '').' ('.($p['hex'] ?? '').") to {$name}",
            'palette.color_changed' => 'changed the color '.($p['color'] ?? '').' to '.($p['hex'] ?? '')." in {$name}",
            'palette.color_fixed' => 'fixed the contrast of '.($p['color'] ?? '').' on '.($p['surface'] ?? 'its surface').': '.($p['from'] ?? '').' to '.($p['hex'] ?? '')." in {$name}",
            'palette.type_changed' => 'set the type of '.$name.' to '.($p['heading'] ?? '').' and '.($p['body'] ?? '').', ratio '.($p['ratio'] ?? ''),
            'palette.kit_shared' => "shared the brand kit of {$name} for approval",
            'palette.public_link_on' => "turned on the public style guide link of {$name}",
            'palette.public_link_off' => "turned off the public style guide link of {$name}",
            'palette.kit_approved' => "approved the brand kit of {$name}",
            'palette.kit_revised' => "started a revision of the brand kit of {$name}",
            'palette.color_removed' => 'removed the color '.($p['color'] ?? '')." from {$name}",
            'proofmark.design_removed' => 'removed the design '.($p['design'] ?? '').' from round '.($p['round'] ?? '')." of {$name}",
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
