<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Enums\ProjectPhase;
use App\Models\ChecklistItem;
use App\Models\CheckRun;
use App\Models\CheckRunResult;
use App\Models\CheckWaiver;
use App\Models\Launch;
use App\Models\Signoff;
use App\Models\User;
use App\Support\Launch\Checks\CheckRegistry;

/**
 * The launch as both portals receive it: explicit arrays, never models.
 */
final class LaunchPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Launch $launch, User $viewer): array
    {
        return [
            'id' => $launch->id,
            'url' => $launch->url,
            'checklist' => $launch->checklistItems
                ->map(fn (ChecklistItem $item) => self::item($item, $viewer))
                ->values(),
            'checks' => self::checks($launch),
            'board' => self::board($launch, $viewer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function board(Launch $launch, User $viewer): array
    {
        $board = GoNoGo::forLaunch($launch);
        $launched = $launch->project->phase === ProjectPhase::Launched;
        $role = $viewer->isAdmin() ? ChecklistOwner::Studio : ChecklistOwner::Client;

        $signoffs = $launch->signoffs()->active()->get()->keyBy(fn (Signoff $signoff) => $signoff->role->value);

        return [
            'rows' => $board->rows,
            'clear' => $board->clear,
            'go' => $board->go,
            'status' => $launched ? 'LAUNCHED' : $board->status(),
            'headline' => $launched ? 'The site is live.' : $board->headline(),
            'launched' => $launched,
            'canSign' => $board->clear && ! $launched && ! $signoffs->has($role->value),
            'signAs' => $role->label(),
            'expectedName' => $viewer->name,
            'canMarkLaunched' => $viewer->isAdmin() && $board->go && ! $launched,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function checks(Launch $launch): array
    {
        $run = $launch->latestCheckRun;
        $waivers = $launch->waivers->keyBy('check_key');
        $labels = CheckRegistry::labels();

        $previous = $run === null ? null : $launch->checkRuns()
            ->where('id', '<', $run->id)
            ->latest('id')
            ->first();

        return [
            'latestRun' => $run === null ? null : self::run($run),
            'previousRunAt' => $previous?->created_at->toIso8601String(),
            'results' => $run === null ? [] : $run->results
                ->map(fn (CheckRunResult $result) => [
                    'key' => $result->check_key,
                    'label' => $labels[$result->check_key] ?? $result->check_key,
                    'status' => $result->status->value,
                    'statusLabel' => $result->status->label(),
                    'message' => $result->message,
                    'details' => $result->details ?? [],
                    'waiver' => self::waiver($waivers->get($result->check_key)),
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function run(CheckRun $run): array
    {
        return [
            'id' => $run->id,
            'ranAt' => $run->created_at->toIso8601String(),
            'ranBy' => $run->run_by_name,
            'url' => $run->url,
            'summary' => $run->summary(),
            'durationMs' => $run->duration_ms,
        ];
    }

    /**
     * @return array{reason: string, by: string, at: string|null}|null
     */
    private static function waiver(?CheckWaiver $waiver): ?array
    {
        return $waiver === null ? null : [
            'reason' => $waiver->reason,
            'by' => $waiver->waived_by_name,
            'at' => $waiver->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function item(ChecklistItem $item, User $viewer): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'hint' => $item->hint,
            'owner' => $item->owner->value,
            'ownerLabel' => $item->owner->label(),
            'checked' => $item->isChecked(),
            'checkedAt' => $item->checked_at?->toIso8601String(),
            'checkedBy' => $item->checked_by_name,
            // The server checks again; this only decides what the UI offers.
            'canToggle' => $viewer->isAdmin() || $item->owner === ChecklistOwner::Client,
        ];
    }
}
