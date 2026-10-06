<?php

namespace App\Support\Sandbox;

use App\Enums\ProjectPhase;
use App\Enums\Role;
use App\Models\ChecklistItem;
use App\Models\Launch;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Activity;
use App\Support\Launch\ChecklistToggle;
use App\Support\Launch\DefaultChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a demo visitor's own copy of the studio (ADR 0004).
 *
 * Runs without a signed-in user, so every workspace_id is set explicitly.
 */
final class SandboxFactory
{
    public function create(): Sandbox
    {
        return DB::transaction(function (): Sandbox {
            $workspace = Workspace::create([
                'name' => DemoTemplate::STUDIO,
                'is_sandbox' => true,
                'expires_at' => now()->addHours(config()->integer('demo.lifetime_hours')),
            ]);

            $token = Str::lower(Str::random(8));
            $domain = config()->string('demo.email_domain');

            $adminPassword = Str::password(16, symbols: false);
            $admin = new User([
                'name' => DemoTemplate::ADMIN_NAME,
                'email' => "demo-{$token}@{$domain}",
                'password' => $adminPassword,
            ]);
            $admin->workspace_id = $workspace->id;
            $admin->role = Role::Admin;
            $admin->email_verified_at = now();
            $admin->save();

            $organizations = [];

            foreach (DemoTemplate::organizations() as $data) {
                $organization = new Organization(['name' => $data['name'], 'website_url' => $data['website']]);
                $organization->workspace_id = $workspace->id;
                $organization->save();
                $organizations[] = $organization;

                Activity::record('organization.created', $organization, ['name' => $organization->name], actor: $admin);

                foreach ($data['projects'] as $projectData) {
                    $project = Project::create([
                        'organization_id' => $organization->id,
                        'name' => $projectData['name'],
                        'description' => $projectData['description'],
                        'phase' => $projectData['phase'],
                        'target_launch_on' => now()->addDays($projectData['launch_in_days'])->toDateString(),
                    ]);

                    $this->recordHistory($project, $projectData['phase'], $admin);
                }
            }

            $clientPassword = Str::password(16, symbols: false);
            $client = new User([
                'name' => DemoTemplate::CLIENT_NAME,
                'email' => "client-{$token}@{$domain}",
                'password' => $clientPassword,
            ]);
            $client->role = Role::Client;
            $client->organization_id = $organizations[0]->id;
            $client->email_verified_at = now();
            $client->save();

            Activity::record('contact.added', $client, [
                'name' => $client->name,
                'organization' => $organizations[0]->name,
            ], actor: $admin);

            $this->createLaunch($workspace, $admin, $client);

            return new Sandbox($workspace, $admin, $adminPassword, $client, $clientPassword);
        });
    }

    /**
     * Launch Control, part-way through its checklist (DemoTemplate::launch()).
     */
    private function createLaunch(Workspace $workspace, User $admin, User $client): void
    {
        $template = DemoTemplate::launch();

        $project = Project::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('name', $template['project'])
            ->firstOrFail();

        $launch = new Launch(['url' => $template['url']]);
        $launch->project_id = $project->id;
        $launch->save();

        DefaultChecklist::addTo($launch);
        Activity::record('launch.created', $launch, ['name' => $project->name], visibleToClient: true, actor: $admin);

        $items = ChecklistItem::withoutGlobalScopes()->where('launch_id', $launch->id)->get();

        foreach ($template['ticked'] as $label => $by) {
            $item = $items->firstWhere('label', $label);

            if ($item !== null) {
                ChecklistToggle::set($item, $by === 'admin' ? $admin : $client, checked: true);
            }
        }
    }

    /**
     * A believable trail: the project was started, then moved phase by phase
     * to where the template puts it.
     */
    private function recordHistory(Project $project, ProjectPhase $phase, User $admin): void
    {
        Activity::record('project.created', $project, ['name' => $project->name], visibleToClient: true, actor: $admin);

        $phases = ProjectPhase::cases();

        for ($i = 1; $i < $phase->step(); $i++) {
            Activity::record('project.phase_changed', $project, [
                'name' => $project->name,
                'from' => $phases[$i - 1]->label(),
                'to' => $phases[$i]->label(),
            ], visibleToClient: true, actor: $admin);
        }
    }
}
