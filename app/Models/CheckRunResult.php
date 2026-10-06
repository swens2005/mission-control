<?php

namespace App\Models;

use App\Enums\CheckStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * One check's outcome within a run.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $check_run_id
 * @property string $check_key
 * @property CheckStatus $status
 * @property string $message
 * @property list<string>|null $details
 * @property int $position
 */
class CheckRunResult extends Model
{
    use BelongsToWorkspace;

    public $timestamps = false;

    protected $table = 'check_results';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => CheckStatus::class,
            'details' => 'array',
        ];
    }
}
