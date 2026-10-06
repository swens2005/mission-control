<?php

namespace App\Support\Launch\Checks;

use App\Support\Http\FetchFailed;

/**
 * Content-Security-Policy, from the response header and/or a <meta
 * http-equiv> tag. Browsers enforce every policy they get, so scripts are
 * only as open as the strictest policy allows (codelaunch.nl itself sends
 * frame-ancestors in a header and the rest in a meta tag, ADR 0006).
 */
final class CspCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'csp';
    }

    public function label(): string
    {
        return 'Content-Security-Policy';
    }

    public function run(CheckContext $context): CheckResult
    {
        $page = $context->page();

        $headerPolicies = $page->headers['content-security-policy'] ?? [];
        $metaPolicies = $this->metaPolicies($context);
        $policies = array_map(self::parse(...), [...$headerPolicies, ...$metaPolicies]);

        if ($policies === []) {
            return ($page->headers['content-security-policy-report-only'] ?? []) !== []
                ? CheckResult::warn('Only a report-only policy is set, so nothing is enforced yet.')
                : CheckResult::fail('No Content-Security-Policy, in a header or a meta tag.');
        }

        $delivery = match (true) {
            $headerPolicies !== [] && $metaPolicies !== [] => 'a header and a meta tag',
            $metaPolicies !== [] => 'a meta tag',
            default => 'a header',
        };

        $restricting = array_values(array_filter($policies, fn (array $policy) => self::scriptSources($policy) !== null));

        if ($restricting === []) {
            return CheckResult::warn("A policy is set in {$delivery}, but it doesn't restrict scripts (no script-src or default-src).");
        }

        $allowsInline = array_all($restricting, fn (array $policy) => self::allowsInline($policy));
        $allowsEval = array_all($restricting, fn (array $policy) => in_array("'unsafe-eval'", self::scriptSources($policy) ?? [], true));

        $problems = [];

        if ($allowsInline) {
            $problems[] = "Inline scripts are allowed ('unsafe-inline').";
        }

        if ($allowsEval) {
            $problems[] = "eval() is allowed ('unsafe-eval').";
        }

        return $problems === []
            ? CheckResult::pass("Set in {$delivery}, and scripts are restricted.")
            : CheckResult::warn("Set in {$delivery}, but it lets risky scripts run.", $problems);
    }

    /**
     * @return list<string>
     */
    private function metaPolicies(CheckContext $context): array
    {
        try {
            $document = $context->document();
        } catch (FetchFailed) {
            return [];
        }

        $policies = [];

        foreach ($document->querySelectorAll('meta[http-equiv]') as $meta) {
            if (strtolower(trim((string) $meta->getAttribute('http-equiv'))) === 'content-security-policy') {
                $policies[] = (string) $meta->getAttribute('content');
            }
        }

        return $policies;
    }

    /**
     * "default-src 'self'; script-src 'self' 'nonce-x'" → ['default-src' => ["'self'"], ...]
     *
     * @return array<string, list<string>>
     */
    public static function parse(string $policy): array
    {
        $directives = [];

        foreach (explode(';', $policy) as $directive) {
            $tokens = preg_split('/\s+/', trim($directive), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($tokens === []) {
                continue;
            }

            $name = strtolower(array_shift($tokens));
            // The first occurrence of a directive wins, as in browsers.
            $directives[$name] ??= array_map(strtolower(...), $tokens);
        }

        return $directives;
    }

    /**
     * script-src, falling back to default-src; null when neither is set.
     *
     * @param  array<string, list<string>>  $policy
     * @return list<string>|null
     */
    private static function scriptSources(array $policy): ?array
    {
        return $policy['script-src'] ?? $policy['default-src'] ?? null;
    }

    /**
     * 'unsafe-inline' is ignored by browsers once a nonce or hash is present.
     *
     * @param  array<string, list<string>>  $policy
     */
    private static function allowsInline(array $policy): bool
    {
        $sources = self::scriptSources($policy) ?? [];

        if (! in_array("'unsafe-inline'", $sources, true)) {
            return false;
        }

        foreach ($sources as $source) {
            if (preg_match("/^'(nonce-|sha256-|sha384-|sha512-)/", $source) === 1) {
                return false;
            }
        }

        return true;
    }
}
