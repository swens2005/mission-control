<?php

namespace App\Support\Launch\Checks;

use Dom\Element;

/**
 * Every image has an alt attribute. alt="" is fine: it marks the image as
 * decorative, so screen readers skip it.
 */
final class ImageAltCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'image-alt';
    }

    public function label(): string
    {
        return 'Image alt text';
    }

    public function run(CheckContext $context): CheckResult
    {
        $images = $context->document()->querySelectorAll('img');
        $total = $images->length;

        if ($total === 0) {
            return CheckResult::pass('No images on the page.');
        }

        $missing = [];
        $decorative = 0;

        foreach ($images as $image) {
            if (! $image->hasAttribute('alt')) {
                $missing[] = $this->describe($image);
            } elseif (trim((string) $image->getAttribute('alt')) === '') {
                $decorative++;
            }
        }

        if ($missing !== []) {
            $details = array_slice($missing, 0, 5);

            if (count($missing) > 5) {
                $details[] = 'and '.(count($missing) - 5).' more';
            }

            return CheckResult::fail(count($missing)." of {$total} images have no alt attribute.", $details);
        }

        return CheckResult::pass("All {$total} images have alt text".($decorative > 0 ? " ({$decorative} marked decorative)." : '.'));
    }

    private function describe(Element $image): string
    {
        $src = (string) $image->getAttribute('src');
        $name = basename((string) parse_url($src, PHP_URL_PATH));

        return $name !== '' ? mb_strimwidth($name, 0, 60, '…') : 'An image without a src';
    }
}
