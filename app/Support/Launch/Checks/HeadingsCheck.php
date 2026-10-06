<?php

namespace App\Support\Launch\Checks;

use App\Enums\CheckStatus;

/**
 * Exactly one h1, and headings that don't skip levels (an h2 followed by an
 * h4), so screen reader users can navigate by heading.
 */
final class HeadingsCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'headings';
    }

    public function label(): string
    {
        return 'Headings';
    }

    public function run(CheckContext $context): CheckResult
    {
        $h1 = 0;
        $previous = 0;
        $skips = [];
        $count = 0;

        foreach ($context->document()->querySelectorAll('h1, h2, h3, h4, h5, h6') as $heading) {
            $count++;
            $level = (int) substr($heading->localName, 1);

            if ($level === 1) {
                $h1++;
            }

            if ($level > $previous + 1) {
                $text = mb_strimwidth(trim((string) $heading->textContent), 0, 50, '…');
                $skips[] = ($previous === 0 ? 'Starts at' : "h{$previous} →")." h{$level}".($text !== '' ? " (“{$text}”)" : '');
            }

            $previous = $level;
        }

        $status = CheckStatus::Pass;
        $notes = [];

        if ($h1 === 0) {
            $status = CheckStatus::Fail;
            $notes[] = 'There is no h1.';
        } elseif ($h1 > 1) {
            $status = CheckStatus::Warn;
            $notes[] = "There are {$h1} h1 headings; one is expected.";
        }

        if ($skips !== []) {
            $status = $status->worst(CheckStatus::Warn);
            $notes[] = count($skips) === 1 ? 'A heading level is skipped.' : count($skips).' heading levels are skipped.';
        }

        return $status === CheckStatus::Pass
            ? CheckResult::pass("One h1, and {$count} headings in a logical order.")
            : new CheckResult($status, implode(' ', $notes), array_slice($skips, 0, 5));
    }
}
