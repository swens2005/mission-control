<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\BrandKitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Palette Lab for one project: colors, type, and the client's approval.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property string $heading_font
 * @property string $body_font
 * @property int $base_size_px
 * @property int $scale_ratio
 * @property int $steps_up
 * @property int $steps_down
 * @property CarbonImmutable|null $shared_at
 * @property string|null $public_token
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by_id
 * @property string|null $approved_by_name
 * @property string|null $approved_ip
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 */
class BrandKit extends Model
{
    /** @use HasFactory<BrandKitFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    /**
     * New kits start from codelaunch.nl's own type: a major third on 16 px.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'heading_font' => 'bricolage',
        'body_font' => 'figtree',
        'base_size_px' => 16,
        'scale_ratio' => 1250,
        'steps_up' => 5,
        'steps_down' => 2,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $kit): void {
            $project = Project::withoutGlobalScopes()->find($kit->project_id);

            $kit->workspace_id ??= $project?->workspace_id;

            if ($project === null || $project->workspace_id !== $kit->workspace_id) {
                throw new LogicException('A brand kit must belong to a project in its own workspace.');
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
     * @return HasMany<Color, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class)->orderBy('position')->orderBy('id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    protected function casts(): array
    {
        return [
            'shared_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
