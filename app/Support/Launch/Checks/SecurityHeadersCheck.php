<?php

namespace App\Support\Launch\Checks;

use App\Enums\CheckStatus;

/**
 * The other security headers: content sniffing, clickjacking, referrer and
 * permissions policy.
 */
final class SecurityHeadersCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'security-headers';
    }

    public function label(): string
    {
        return 'Other security headers';
    }

    public function run(CheckContext $context): CheckResult
    {
        $page = $context->page();
        $status = CheckStatus::Pass;
        $problems = [];

        if (! str_contains(strtolower((string) $page->header('x-content-type-options')), 'nosniff')) {
            $status = $status->worst(CheckStatus::Fail);
            $problems[] = 'X-Content-Type-Options: nosniff is missing.';
        }

        if (! $this->blocksFraming($page->header('x-frame-options'), $page->headers['content-security-policy'] ?? [])) {
            $status = $status->worst(CheckStatus::Fail);
            $problems[] = "Nothing stops other sites framing this one (X-Frame-Options or CSP frame-ancestors; a meta tag can't do this).";
        }

        if ($page->header('referrer-policy') === null) {
            $status = $status->worst(CheckStatus::Warn);
            $problems[] = 'Referrer-Policy is missing.';
        }

        if ($page->header('permissions-policy') === null) {
            $status = $status->worst(CheckStatus::Warn);
            $problems[] = 'Permissions-Policy is missing.';
        }

        return match ($status) {
            CheckStatus::Pass => CheckResult::pass('Nosniff, clickjacking protection, Referrer-Policy and Permissions-Policy are all set.'),
            default => new CheckResult($status, count($problems) === 1 ? '1 header needs attention.' : count($problems).' headers need attention.', $problems),
        };
    }

    /**
     * @param  list<string>  $cspHeaders
     */
    private function blocksFraming(?string $frameOptions, array $cspHeaders): bool
    {
        if (in_array(strtolower(trim((string) $frameOptions)), ['deny', 'sameorigin'], true)) {
            return true;
        }

        foreach ($cspHeaders as $policy) {
            if (isset(CspCheck::parse($policy)['frame-ancestors'])) {
                return true;
            }
        }

        return false;
    }
}
