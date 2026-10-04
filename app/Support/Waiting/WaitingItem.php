<?php

namespace App\Support\Waiting;

use Carbon\CarbonImmutable;

/**
 * One thing a client needs to do, e.g. "Approve design round 2".
 */
final readonly class WaitingItem
{
    public function __construct(
        public string $title,
        public string $module,
        public int $projectId,
        public string $projectName,
        public string $url,
        public ?CarbonImmutable $dueOn = null,
    ) {}

    /**
     * @return array{title: string, module: string, projectId: int, projectName: string, url: string, dueOn: string|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'module' => $this->module,
            'projectId' => $this->projectId,
            'projectName' => $this->projectName,
            'url' => $this->url,
            'dueOn' => $this->dueOn?->toDateString(),
        ];
    }
}
