<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Support\Proofmark\DesignFiles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes expired demo sandboxes. Everything inside a workspace (users,
 * clients, projects, activity) goes with it through the foreign keys; its
 * Proofmark uploads are deleted from disk here (ADR 0009).
 * Scheduled hourly in routes/console.php; needs the cPanel cron job.
 */
#[Signature('sandbox:prune')]
#[Description('Delete demo sandboxes that have expired, with their uploads')]
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
                    DesignFiles::purge($workspace->id);
                    $deleted++;
                }
            });

        // Catches folders left behind if a run was cut off half-way.
        $orphans = DesignFiles::sweep();

        $this->info("Deleted {$deleted} expired sandbox(es) and {$orphans} orphaned upload folder(s).");

        return self::SUCCESS;
    }
}
