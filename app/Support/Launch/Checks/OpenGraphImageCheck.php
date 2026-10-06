<?php

namespace App\Support\Launch\Checks;

use Dom\Element;

/**
 * og:image: the picture shown when the site is shared on social media or
 * in chat apps.
 */
final class OpenGraphImageCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'og-image';
    }

    public function label(): string
    {
        return 'Open Graph image';
    }

    public function run(CheckContext $context): CheckResult
    {
        $meta = $context->document()->querySelector('meta[property="og:image" i]');
        $reference = $meta instanceof Element ? trim((string) $meta->getAttribute('content')) : '';

        if ($reference === '') {
            return CheckResult::fail('No og:image, so shared links show without a picture.');
        }

        $isAbsolute = preg_match('#^https?://#i', $reference) === 1;
        $image = $context->fetch($context->absolute($reference), 'HEAD');

        if (! $image->successful()) {
            return CheckResult::fail("The og:image answered with HTTP {$image->status}.");
        }

        $type = strtolower((string) $image->header('content-type'));

        if ($type !== '' && ! str_starts_with($type, 'image/')) {
            return CheckResult::warn("The og:image isn't served as an image ({$type}).");
        }

        return $isAbsolute
            ? CheckResult::pass('Set, and the image loads'.($type !== '' ? " ({$type})." : '.'))
            : CheckResult::warn('The image loads, but og:image should be a full URL; some apps ignore relative ones.');
    }
}
