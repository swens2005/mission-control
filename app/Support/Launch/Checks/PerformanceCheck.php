<?php

namespace App\Support\Launch\Checks;

use App\Enums\CheckStatus;

/**
 * How fast the page answers and how heavy its HTML is. Measured from this
 * server, and the HTML document only: images, scripts and fonts aren't
 * fetched (docs/backlog.md).
 */
final class PerformanceCheck implements LaunchCheck
{
    public const FAST_MS = 800;

    public const SLOW_MS = 2000;

    public const LIGHT_BYTES = 500 * 1024;

    public const HEAVY_BYTES = 1024 * 1024;

    public function key(): string
    {
        return 'performance';
    }

    public function label(): string
    {
        return 'Response time and page weight';
    }

    public function run(CheckContext $context): CheckResult
    {
        $page = $context->page();
        $ms = $page->timeMs;
        $bytes = strlen($page->body);
        $kb = (int) ceil($bytes / 1024);

        $time = match (true) {
            $ms <= self::FAST_MS => CheckStatus::Pass,
            $ms <= self::SLOW_MS => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        $weight = match (true) {
            $bytes <= self::LIGHT_BYTES => CheckStatus::Pass,
            $bytes <= self::HEAVY_BYTES => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        $details = [];

        if ($time !== CheckStatus::Pass) {
            $details[] = 'Aim for an answer within '.self::FAST_MS.' ms.';
        }

        if ($weight !== CheckStatus::Pass) {
            $details[] = 'Aim for HTML under 500 KB.';
        }

        $details[] = 'Measured from this server; images, scripts and fonts are not included.';

        return new CheckResult($time->worst($weight), "The page answered in {$ms} ms with {$kb} KB of HTML.", $details);
    }
}
