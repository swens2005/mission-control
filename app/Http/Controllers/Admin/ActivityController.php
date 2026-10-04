<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityEntry;
use App\Models\Project;
use App\Support\ActivityFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The whole workspace's audit trail. Only reachable through the admin portal
 * group, and the workspace scope limits it to the admin's own studio.
 */
class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'project' => ['nullable', 'integer'],
        ]);

        $entries = ActivityEntry::query()
            ->with(['actor', 'project'])
            ->when($filters['project'] ?? null, fn ($query, $id) => $query->where('project_id', $id))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ActivityEntry $entry) => ActivityFeed::present($entry));

        return Inertia::render('admin/activity/index', [
            'entries' => $entries,
            'filters' => ['project' => isset($filters['project']) ? (int) $filters['project'] : null],
            'projects' => Project::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Project $project) => ['id' => $project->id, 'name' => $project->name])
                ->all(),
        ]);
    }
}
