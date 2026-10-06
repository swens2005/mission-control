<?php

namespace App\Support\Launch;

/**
 * The go/no-go board: one row per area, CLEAR when the checks and the
 * checklist are done, GO when both sides have also signed.
 */
final readonly class Board
{
    /**
     * @param  list<array{key: string, label: string, ok: bool, detail: string}>  $rows
     */
    public function __construct(
        public array $rows,
        public bool $clear,
        public bool $go,
    ) {}

    /**
     * What still stands between NO-GO and GO, in plain words.
     *
     * @return list<string>
     */
    public function reasons(): array
    {
        $reasons = [];

        foreach ($this->rows as $row) {
            if (! $row['ok']) {
                $reasons[] = "{$row['label']}: {$row['detail']}";
            }
        }

        return $reasons;
    }

    /**
     * The one-word status for the big display.
     */
    public function status(): string
    {
        return match (true) {
            $this->go => 'GO',
            $this->clear => 'CLEAR',
            default => 'NO-GO',
        };
    }

    /**
     * The sentence under it: what the status means, or what blocks GO.
     */
    public function headline(): string
    {
        return match (true) {
            $this->go => 'Everything is ready and both sides have signed.',
            $this->clear => 'Checks and checklist are done. Waiting for sign-off.',
            default => $this->reasons()[0] ?? 'Not ready yet.',
        };
    }
}
