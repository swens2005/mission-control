<?php

namespace App\Models;

use App\Enums\IntakeBudget;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\IntakeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Scope: the client's answers about what they need (story 25). The studio
 * asks for it; the client saves drafts and sends it once.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property string|null $goals
 * @property list<string>|null $pages
 * @property list<string>|null $features
 * @property string|null $features_other
 * @property IntakeBudget|null $budget
 * @property CarbonImmutable|null $deadline_on
 * @property string|null $deadline_reason
 * @property CarbonImmutable $requested_at
 * @property CarbonImmutable|null $submitted_at
 * @property int|null $submitted_by_id
 * @property string|null $submitted_by_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 */
class Intake extends Model
{
    /** @use HasFactory<IntakeFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $intake): void {
            $project = Project::withoutGlobalScopes()->find($intake->project_id);

            $intake->workspace_id ??= $project?->workspace_id;

            if ($project === null || $project->workspace_id !== $intake->workspace_id) {
                throw new LogicException('An intake must belong to a project in its own workspace.');
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

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    protected function casts(): array
    {
        return [
            'pages' => 'array',
            'features' => 'array',
            'budget' => IntakeBudget::class,
            'deadline_on' => 'date',
            'requested_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
