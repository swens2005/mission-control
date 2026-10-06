<?php

namespace App\Support\Launch;

use App\Enums\CheckStatus;
use App\Models\CheckRun;
use App\Models\CheckRunResult;
use App\Models\CheckWaiver;
use App\Models\Launch;
use App\Models\User;
use App\Support\Activity;
use App\Support\Launch\Checks\CheckRegistry;
use App\Support\Launch\Checks\CheckRunner;
use Illuminate\Support\Facades\DB;

/**
 * Runs the automated checks for a launch and stores the outcome; manages
 * waivers. Callers authorize and rate limit first.
 */
final class LaunchChecks
{
    public function __construct(private readonly CheckRunner $runner) {}

    public function run(Launch $launch, User $user): CheckRun
    {
        $started = hrtime(true);
        $rows = $this->runner->run($launch->url);
        $durationMs = (int) round((hrtime(true) - $started) / 1_000_000);

        $count = fn (CheckStatus $status): int => count(array_filter($rows, fn (array $row) => $row['result']->status === $status));

        return DB::transaction(function () use ($launch, $user, $rows, $durationMs, $count): CheckRun {
            $run = new CheckRun;
            $run->forceFill([
                'workspace_id' => $launch->workspace_id,
                'launch_id' => $launch->id,
                'run_by_id' => $user->id,
                'run_by_name' => $user->name,
                'url' => $launch->url,
                'passed' => $count(CheckStatus::Pass),
                'warned' => $count(CheckStatus::Warn),
                'failed' => $count(CheckStatus::Fail),
                'skipped' => $count(CheckStatus::Skipped),
                'duration_ms' => $durationMs,
            ])->save();

            foreach ($rows as $position => ['check' => $check, 'result' => $result]) {
                (new CheckRunResult)->forceFill([
                    'workspace_id' => $launch->workspace_id,
                    'check_run_id' => $run->id,
                    'check_key' => $check->key(),
                    'status' => $result->status,
                    'message' => mb_strimwidth($result->message, 0, 500, '…'),
                    'details' => $result->details === [] ? null : $result->details,
                    'position' => $position,
                ])->save();
            }

            Activity::record('launch.checked', $launch, [
                'name' => $launch->project->name,
                'summary' => $run->summary(),
            ], visibleToClient: true, actor: $user);

            return $run;
        });
    }

    public function waive(Launch $launch, string $checkKey, string $reason, User $user): CheckWaiver
    {
        $waiver = CheckWaiver::query()
            ->where('launch_id', $launch->id)
            ->where('check_key', $checkKey)
            ->first() ?? new CheckWaiver;
        $waiver->forceFill([
            'workspace_id' => $launch->workspace_id,
            'launch_id' => $launch->id,
            'check_key' => $checkKey,
            'reason' => $reason,
            'waived_by_id' => $user->id,
            'waived_by_name' => $user->name,
        ])->save();

        Activity::record('check.waived', $launch, [
            'name' => CheckRegistry::labels()[$checkKey] ?? $checkKey,
            'project' => $launch->project->name,
            'reason' => $reason,
        ], visibleToClient: true, actor: $user);

        return $waiver;
    }

    public function withdraw(Launch $launch, string $checkKey, User $user): void
    {
        $deleted = CheckWaiver::query()
            ->where('launch_id', $launch->id)
            ->where('check_key', $checkKey)
            ->delete();

        if ($deleted > 0) {
            Activity::record('check.unwaived', $launch, [
                'name' => CheckRegistry::labels()[$checkKey] ?? $checkKey,
                'project' => $launch->project->name,
            ], visibleToClient: true, actor: $user);
        }
    }
}
