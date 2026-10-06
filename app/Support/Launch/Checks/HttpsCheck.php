<?php

namespace App\Support\Launch\Checks;

use App\Support\Http\FetchFailed;

/**
 * The site is served over HTTPS, and plain http:// redirects there.
 */
final class HttpsCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'https';
    }

    public function label(): string
    {
        return 'HTTPS and redirect';
    }

    public function run(CheckContext $context): CheckResult
    {
        $page = $context->page();

        if (! str_starts_with($page->url, 'https://')) {
            return CheckResult::fail('The site is served over plain http, not HTTPS.');
        }

        $host = (string) parse_url($page->url, PHP_URL_HOST);

        try {
            $plain = $context->fetch("http://{$host}/");
        } catch (FetchFailed) {
            return CheckResult::warn("Served over HTTPS, but http://{$host}/ couldn't be reached to check its redirect.");
        }

        return str_starts_with($plain->url, 'https://')
            ? CheckResult::pass('Served over HTTPS, and http:// redirects to it.')
            : CheckResult::fail("http://{$host}/ doesn't redirect to HTTPS.");
    }
}
