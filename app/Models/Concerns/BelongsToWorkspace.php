<?php

namespace App\Models\Concerns;

use App\Models\Scopes\WorkspaceScope;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes a model to the signed-in user's workspace and fills workspace_id on
 * create. workspace_id is never mass assignable. See ADR 0004.
 *
 * @property int $workspace_id
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);

        static::saving(function (self $model): void {
            $model->workspace_id ??= Workspace::currentId();
        });
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
