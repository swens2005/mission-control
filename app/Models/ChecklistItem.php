<?php

namespace App\Models;

use App\Enums\ChecklistOwner;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One manual pre-flight item ("Favicon"), owned by the studio or the client.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $launch_id
 * @property string $label
 * @property string|null $hint
 * @property ChecklistOwner $owner
 * @property int $position
 * @property CarbonImmutable|null $checked_at
 * @property int|null $checked_by_id
 * @property string|null $checked_by_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Launch $launch
 */
#[Fillable(['label', 'hint', 'owner', 'position'])]
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use BelongsToWorkspace, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $launch = Launch::withoutGlobalScopes()->find($item->launch_id);

            $item->workspace_id ??= $launch?->workspace_id;

            if ($launch === null || $launch->workspace_id !== $item->workspace_id) {
                throw new LogicException('A checklist item must belong to a launch in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Launch, $this>
     */
    public function launch(): BelongsTo
    {
        return $this->belongsTo(Launch::class);
    }

    public function isChecked(): bool
    {
        return $this->checked_at !== null;
    }

    public function check(User $user): void
    {
        $this->forceFill([
            'checked_at' => now(),
            'checked_by_id' => $user->id,
            'checked_by_name' => $user->name,
        ])->save();
    }

    public function uncheck(): void
    {
        $this->forceFill([
            'checked_at' => null,
            'checked_by_id' => null,
            'checked_by_name' => null,
        ])->save();
    }

    protected function casts(): array
    {
        return [
            'owner' => ChecklistOwner::class,
            'checked_at' => 'datetime',
        ];
    }
}
