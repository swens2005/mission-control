<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One run of the automated launch checks. Written once by
 * App\Support\Launch\LaunchChecks; never edited.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $launch_id
 * @property int|null $run_by_id
 * @property string $run_by_name
 * @property string $url
 * @property int $passed
 * @property int $warned
 * @property int $failed
 * @property int $skipped
 * @property int $duration_ms
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Launch $launch
 */
class CheckRun extends Model
{
    use BelongsToWorkspace;

    /** Nothing is mass assignable: LaunchChecks sets every field. */
    protected $guarded = ['*'];

    /**
     * @return BelongsTo<Launch, $this>
     */
    public function launch(): BelongsTo
    {
        return $this->belongsTo(Launch::class);
    }

    /**
     * @return HasMany<CheckRunResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(CheckRunResult::class)->orderBy('position');
    }

    /**
     * "9 passed, 1 warning" for the activity log and the page.
     */
    public function summary(): string
    {
        $parts = ["{$this->passed} passed"];

        if ($this->warned > 0) {
            $parts[] = $this->warned === 1 ? '1 warning' : "{$this->warned} warnings";
        }

        if ($this->failed > 0) {
            $parts[] = "{$this->failed} failed";
        }

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} skipped";
        }

        return implode(', ', $parts);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
