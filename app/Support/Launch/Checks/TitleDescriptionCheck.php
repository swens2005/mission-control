<?php

namespace App\Support\Launch\Checks;

use App\Enums\CheckStatus;
use Dom\Element;

/**
 * A title and a meta description, both a sensible length for search
 * results.
 */
final class TitleDescriptionCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'title-description';
    }

    public function label(): string
    {
        return 'Title and meta description';
    }

    public function run(CheckContext $context): CheckResult
    {
        $document = $context->document();

        $title = trim($document->title);
        $meta = $document->querySelector('meta[name="description" i]');
        $description = $meta instanceof Element ? trim((string) $meta->getAttribute('content')) : '';

        $status = CheckStatus::Pass;
        $notes = [];

        $titleLength = mb_strlen($title);

        if ($title === '') {
            $status = $status->worst(CheckStatus::Fail);
            $notes[] = 'The page has no title.';
        } elseif ($titleLength < 10 || $titleLength > 70) {
            $status = $status->worst(CheckStatus::Warn);
            $notes[] = "The title is {$titleLength} characters; 10 to 70 shows best in search results.";
        }

        $descriptionLength = mb_strlen($description);

        if ($description === '') {
            $status = $status->worst(CheckStatus::Fail);
            $notes[] = 'There is no meta description.';
        } elseif ($descriptionLength < 50 || $descriptionLength > 160) {
            $status = $status->worst(CheckStatus::Warn);
            $notes[] = "The description is {$descriptionLength} characters; 50 to 160 shows best in search results.";
        }

        if ($notes === []) {
            return CheckResult::pass("Title ({$titleLength} characters) and description ({$descriptionLength} characters) are both set.");
        }

        return new CheckResult($status, array_shift($notes), $notes);
    }
}
