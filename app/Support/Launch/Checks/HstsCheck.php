<?php

namespace App\Support\Launch\Checks;

/**
 * Strict-Transport-Security tells browsers to use HTTPS only, even when
 * someone types http://.
 */
final class HstsCheck implements LaunchCheck
{
    /** 180 days, the usual minimum recommendation. */
    public const MIN_MAX_AGE = 15_552_000;

    public function key(): string
    {
        return 'hsts';
    }

    public function label(): string
    {
        return 'HSTS';
    }

    public function run(CheckContext $context): CheckResult
    {
        $page = $context->page();

        if (! str_starts_with($page->url, 'https://')) {
            return CheckResult::fail('HSTS needs the site to be on HTTPS first.');
        }

        $header = $page->header('strict-transport-security');

        if ($header === null) {
            return CheckResult::fail('No Strict-Transport-Security header.');
        }

        if (preg_match('/max-age\s*=\s*"?(\d+)/i', $header, $matches) !== 1 || (int) $matches[1] === 0) {
            return CheckResult::fail('The Strict-Transport-Security header has no usable max-age.');
        }

        $days = intdiv((int) $matches[1], 86_400);
        $details = str_contains(strtolower($header), 'includesubdomains') ? ['Includes subdomains.'] : [];

        return (int) $matches[1] < self::MIN_MAX_AGE
            ? CheckResult::warn("max-age is {$days} days; at least 180 days is recommended.", $details)
            : CheckResult::pass("Set, with a max-age of {$days} days.", $details);
    }
}
