<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The studio's decision to accept a failing or warning check for this
 * launch, with a reason. Belongs to the launch, so it survives new runs.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $launch_id
 * @property string $check_key
 * @property string $reason
 * @property int|null $waived_by_id
 * @property string $waived_by_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class CheckWaiver extends Model
{
    use BelongsToWorkspace;

    protected $guarded = ['*'];

    /**
     * @return BelongsTo<Launch, $this>
     */
    public function launch(): BelongsTo
    {
        return $this->belongsTo(Launch::class);
    }
}
