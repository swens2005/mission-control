<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\LaunchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Launch Control for one project: the site URL to check and the pre-flight
 * checklist.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property string $url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 */
#[Fillable(['url'])]
class Launch extends Model
{
    /** @use HasFactory<LaunchFactory> */
    use BelongsToWorkspace, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $launch): void {
            $project = Project::withoutGlobalScopes()->find($launch->project_id);

            // Outside a request (the sandbox), inherit the workspace.
            $launch->workspace_id ??= $project?->workspace_id;

            if ($project === null || $project->workspace_id !== $launch->workspace_id) {
                throw new LogicException('A launch must belong to a project in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<CheckRun, $this>
     */
    public function checkRuns(): HasMany
    {
        return $this->hasMany(CheckRun::class);
    }

    /**
     * @return HasOne<CheckRun, $this>
     */
    public function latestCheckRun(): HasOne
    {
        return $this->hasOne(CheckRun::class)->latestOfMany();
    }

    /**
     * @return HasMany<Signoff, $this>
     */
    public function signoffs(): HasMany
    {
        return $this->hasMany(Signoff::class);
    }

    /**
     * @return HasMany<CheckWaiver, $this>
     */
    public function waivers(): HasMany
    {
        return $this->hasMany(CheckWaiver::class);
    }

    /**
     * @return HasMany<ChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position')->orderBy('id');
    }
}
