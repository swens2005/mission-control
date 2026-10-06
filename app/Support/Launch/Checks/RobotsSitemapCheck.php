<?php

namespace App\Support\Launch\Checks;

use App\Support\Http\FetchedResponse;
use App\Support\Http\FetchFailed;

/**
 * robots.txt and sitemap.xml are reachable, and robots.txt doesn't block
 * every crawler (a classic leftover from the staging site).
 */
final class RobotsSitemapCheck implements LaunchCheck
{
    public function key(): string
    {
        return 'robots-sitemap';
    }

    public function label(): string
    {
        return 'robots.txt and sitemap.xml';
    }

    public function run(CheckContext $context): CheckResult
    {
        $origin = $context->origin();
        $robots = $this->text($context, "{$origin}/robots.txt");

        if ($robots !== null && self::blocksEverything($robots)) {
            return CheckResult::fail('robots.txt tells every search engine to stay away (Disallow: /).');
        }

        $sitemapUrl = ($robots !== null ? self::sitemapUrls($robots)[0] ?? null : null) ?? "{$origin}/sitemap.xml";
        $sitemap = $this->text($context, $sitemapUrl);
        $sitemapOk = $sitemap !== null && (str_contains($sitemap, '<urlset') || str_contains($sitemap, '<sitemapindex'));

        return match (true) {
            $robots === null && ! $sitemapOk => CheckResult::fail('Neither robots.txt nor a sitemap could be found.'),
            $robots === null => CheckResult::warn('The sitemap is there, but robots.txt is missing.'),
            ! $sitemapOk => CheckResult::warn('robots.txt is there, but no sitemap was found.', [$sitemapUrl]),
            default => CheckResult::pass('Both are in place; the sitemap lists '.self::urlCount((string) $sitemap).'.'),
        };
    }

    /**
     * The body of a successful, non-HTML response; null otherwise. Sites that
     * answer every path with their home page don't count as having the file.
     */
    private function text(CheckContext $context, string $url): ?string
    {
        try {
            $response = $context->fetch($url);
        } catch (FetchFailed) {
            return null;
        }

        return $response->successful() && ! $this->isHtml($response) ? $response->body : null;
    }

    private function isHtml(FetchedResponse $response): bool
    {
        return str_contains(strtolower((string) $response->header('content-type')), 'text/html')
            || str_starts_with(ltrim(strtolower($response->body)), '<!doctype html');
    }

    /**
     * True when the group for every crawler ("User-agent: *") has "Disallow: /".
     */
    public static function blocksEverything(string $robots): bool
    {
        $agents = [];
        $inRules = false;

        foreach (preg_split('/\R/', $robots) ?: [] as $line) {
            $line = trim((string) preg_replace('/#.*/', '', $line));

            if (! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map(trim(...), explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                // A user-agent line after rules starts a new group.
                $agents = $inRules ? [$value] : [...$agents, $value];
                $inRules = false;

                continue;
            }

            if ($field === 'disallow' || $field === 'allow') {
                $inRules = true;

                if ($field === 'disallow' && $value === '/' && in_array('*', $agents, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function urlCount(string $sitemap): string
    {
        $count = substr_count($sitemap, '<loc>');

        return $count === 1 ? '1 URL' : "{$count} URLs";
    }

    /**
     * @return list<string>
     */
    public static function sitemapUrls(string $robots): array
    {
        preg_match_all('/^\s*sitemap:\s*(\S+)/im', $robots, $matches);

        return $matches[1];
    }
}
