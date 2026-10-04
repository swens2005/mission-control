<?php

namespace App\Models\Scopes;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits queries to the signed-in user's workspace (ADR 0004).
 *
 * With nobody signed in (console, scheduler, the login lookup itself) there
 * is no implicit scope, so code running there must filter explicitly.
 *
 * @implements Scope<Model>
 */
class WorkspaceScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $workspaceId = Workspace::currentId();

        if ($workspaceId !== null) {
            $builder->where($model->qualifyColumn('workspace_id'), $workspaceId);
        }
    }
}
