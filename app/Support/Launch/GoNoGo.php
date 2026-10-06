<?php

namespace App\Support\Launch;

use App\Enums\ChecklistOwner;
use App\Enums\CheckStatus;
use App\Models\ChecklistItem;
use App\Models\CheckRunResult;
use App\Models\CheckWaiver;
use App\Models\Launch;
use App\Models\Signoff;

/**
 * Decides the go/no-go board. evaluate() is a pure function, unit tested
 * for every combination; forLaunch() gathers its inputs.
 */
final class GoNoGo
{
    /**
     * @param  array<string, CheckStatus>|null  $checksByKey  the latest run, by check key; null when never run
     * @param  array<int, string>  $waived  check keys with a waiver
     * @param  bool  $checksCurrent  the latest run checked the launch's current URL
     * @param  array<int, array{owner: ChecklistOwner, checked: bool}>  $checklist
     */
    public static function evaluate(
        ?array $checksByKey,
        array $waived,
        bool $checksCurrent,
        array $checklist,
        ?string $studioSigner,
        ?string $clientSigner,
    ): Board {
        $checksRow = self::checksRow($checksByKey, $waived, $checksCurrent);
        $studioRow = self::checklistRow('studio-checklist', 'Studio checklist', ChecklistOwner::Studio, $checklist);
        $clientRow = self::checklistRow('client-checklist', 'Client checklist', ChecklistOwner::Client, $checklist);

        $clear = $checksRow['ok'] && $studioRow['ok'] && $clientRow['ok'];

        $rows = [
            $checksRow,
            $studioRow,
            $clientRow,
            self::signoffRow('studio-signoff', 'Studio sign-off', $studioSigner, $clear),
            self::signoffRow('client-signoff', 'Client sign-off', $clientSigner, $clear),
        ];

        return new Board($rows, $clear, $clear && $studioSigner !== null && $clientSigner !== null);
    }

    public static function forLaunch(Launch $launch): Board
    {
        // Always fresh: this runs right after writes that change the answer.
        $run = $launch->latestCheckRun()->with('results')->first();

        $checksByKey = $run?->results
            ->mapWithKeys(fn (CheckRunResult $result) => [$result->check_key => $result->status])
            ->all();

        $signers = $launch->signoffs()->active()->get()
            ->mapWithKeys(fn (Signoff $signoff) => [$signoff->role->value => $signoff->name_typed]);

        return self::evaluate(
            checksByKey: $checksByKey,
            waived: $launch->waivers()->get()
                ->map(fn (CheckWaiver $waiver) => $waiver->check_key)
                ->values()
                ->all(),
            checksCurrent: $run !== null && $run->url === $launch->url,
            checklist: $launch->checklistItems()->get()
                ->map(fn (ChecklistItem $item) => ['owner' => $item->owner, 'checked' => $item->isChecked()])
                ->values()
                ->all(),
            studioSigner: $signers->get(ChecklistOwner::Studio->value),
            clientSigner: $signers->get(ChecklistOwner::Client->value),
        );
    }

    /**
     * @param  array<string, CheckStatus>|null  $checksByKey
     * @param  array<int, string>  $waived
     * @return array{key: string, label: string, ok: bool, detail: string}
     */
    private static function checksRow(?array $checksByKey, array $waived, bool $current): array
    {
        $row = ['key' => 'checks', 'label' => 'Automated checks'];

        if ($checksByKey === null) {
            return [...$row, 'ok' => false, 'detail' => 'not run yet.'];
        }

        if (! $current) {
            return [...$row, 'ok' => false, 'detail' => 'they ran against an older site URL; run them again.'];
        }

        $open = [CheckStatus::Fail->value => 0, CheckStatus::Warn->value => 0, CheckStatus::Skipped->value => 0];
        $waivedCount = 0;

        foreach ($checksByKey as $key => $status) {
            if ($status === CheckStatus::Pass) {
                continue;
            }

            if (in_array($key, $waived, true)) {
                $waivedCount++;

                continue;
            }

            $open[$status->value]++;
        }

        $parts = array_filter([
            $open['fail'] > 0 ? "{$open['fail']} failing" : null,
            $open['warn'] > 0 ? ($open['warn'] === 1 ? '1 warning' : "{$open['warn']} warnings") : null,
            $open['skipped'] > 0 ? "{$open['skipped']} skipped" : null,
        ]);

        if ($parts !== []) {
            return [...$row, 'ok' => false, 'detail' => implode(', ', $parts).'.'];
        }

        $total = count($checksByKey);

        return [...$row, 'ok' => true, 'detail' => $waivedCount > 0
            ? ($total - $waivedCount)." passed, {$waivedCount} waived."
            : "all {$total} passed."];
    }

    /**
     * @param  array<int, array{owner: ChecklistOwner, checked: bool}>  $checklist
     * @return array{key: string, label: string, ok: bool, detail: string}
     */
    private static function checklistRow(string $key, string $label, ChecklistOwner $owner, array $checklist): array
    {
        $items = array_filter($checklist, fn (array $item) => $item['owner'] === $owner);
        $open = count(array_filter($items, fn (array $item) => ! $item['checked']));

        return [
            'key' => $key,
            'label' => $label,
            'ok' => $open === 0,
            'detail' => match (true) {
                $items === [] => 'nothing on it.',
                $open === 0 => 'all '.count($items).' ticked.',
                default => $open === 1 ? '1 item open.' : "{$open} items open.",
            },
        ];
    }

    /**
     * @return array{key: string, label: string, ok: bool, detail: string}
     */
    private static function signoffRow(string $key, string $label, ?string $signer, bool $clear): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $signer !== null,
            'detail' => match (true) {
                $signer !== null => "signed by {$signer}.",
                $clear => 'waiting for a signature.',
                default => 'opens once the board is clear.',
            },
        ];
    }
}
