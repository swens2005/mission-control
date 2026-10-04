<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes expired demo sandboxes. Everything inside a workspace (users,
 * clients, projects, activity) goes with it through the foreign keys.
 * Scheduled hourly in routes/console.php; needs the cPanel cron job.
 */
#[Signature('sandbox:prune')]
#[Description('Delete demo sandboxes that have expired')]
class PruneSandboxes extends Command
{
    public function handle(): int
    {
        $deleted = 0;

        Workspace::query()
            ->where('is_sandbox', true)
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($workspaces) use (&$deleted) {
                foreach ($workspaces as $workspace) {
                    $workspace->delete();
                    $deleted++;
                }
            });

        $this->info("Deleted {$deleted} expired sandbox(es).");

        return self::SUCCESS;
    }
}
