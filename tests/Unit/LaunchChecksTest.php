<?php

use App\Enums\CheckStatus;
use App\Support\Http\FetchedResponse;
use App\Support\Http\FetchFailed;
use App\Support\Http\Resolver;
use App\Support\Http\SafeFetcher;
use App\Support\Launch\Checks\CheckContext;
use App\Support\Launch\Checks\CheckResult;
use App\Support\Launch\Checks\CheckRunner;
use App\Support\Launch\Checks\CspCheck;
use App\Support\Launch\Checks\HeadingsCheck;
use App\Support\Launch\Checks\HstsCheck;
use App\Support\Launch\Checks\HttpsCheck;
use App\Support\Launch\Checks\ImageAltCheck;
use App\Support\Launch\Checks\LaunchCheck;
use App\Support\Launch\Checks\OpenGraphImageCheck;
use App\Support\Launch\Checks\PerformanceCheck;
use App\Support\Launch\Checks\RobotsSitemapCheck;
use App\Support\Launch\Checks\SecurityHeadersCheck;
use App\Support\Launch\Checks\TitleDescriptionCheck;

const SITE = 'https://site.example/';

/**
 * @param  array<string, string|list<string>>  $headers
 */
function siteResponse(string $body = '', array $headers = [], int $status = 200, string $url = SITE, int $timeMs = 300): FetchedResponse
{
    $normalized = [];

    foreach ($headers as $name => $value) {
        $normalized[strtolower($name)] = is_array($value) ? $value : [$value];
    }

    return new FetchedResponse($url, $status, $normalized, $body, $timeMs);
}

/**
 * The page plus any other fixtures, keyed "METHOD url".
 *
 * @param  array<string, FetchedResponse|FetchFailed>  $others
 */
function siteContext(FetchedResponse $page, array $others = []): CheckContext
{
    return CheckContext::fake(SITE, ['GET '.SITE => $page, ...$others]);
}

function siteHtml(string $head = '', string $body = '<h1>Home</h1>'): string
{
    return "<!doctype html><html><head>{$head}</head><body>{$body}</body></html>";
}

function runOneCheck(LaunchCheck $check, CheckContext $context): CheckResult
{
    return (new CheckRunner(unusedFetcher()))->run(SITE, [$check], $context)[0]['result'];
}

/**
 * The runner needs a fetcher, but fake contexts never use it.
 */
function unusedFetcher(): SafeFetcher
{
    return new SafeFetcher(new class implements Resolver
    {
        public function resolve(string $host): array
        {
            return [];
        }
    });
}

// HTTPS

test('https passes when http redirects to https', function () {
    $context = siteContext(siteResponse(), ['GET http://site.example/' => siteResponse(url: SITE)]);

    expect(runOneCheck(new HttpsCheck, $context)->status)->toBe(CheckStatus::Pass);
});

test('https fails when http does not redirect, or the site is plain http', function () {
    $noRedirect = siteContext(siteResponse(), ['GET http://site.example/' => siteResponse(url: 'http://site.example/')]);
    $plain = siteContext(siteResponse(url: 'http://site.example/'));

    expect(runOneCheck(new HttpsCheck, $noRedirect)->status)->toBe(CheckStatus::Fail)
        ->and(runOneCheck(new HttpsCheck, $plain)->status)->toBe(CheckStatus::Fail);
});

test('https warns when http cannot be reached', function () {
    expect(runOneCheck(new HttpsCheck, siteContext(siteResponse()))->status)->toBe(CheckStatus::Warn);
});

// HSTS

test('hsts by max-age', function (?string $header, CheckStatus $expected) {
    $headers = $header === null ? [] : ['Strict-Transport-Security' => $header];

    expect(runOneCheck(new HstsCheck, siteContext(siteResponse(headers: $headers)))->status)->toBe($expected);
})->with([
    'one year' => ['max-age=31536000; includeSubDomains', CheckStatus::Pass],
    'quoted' => ['max-age="31536000"', CheckStatus::Pass],
    'one day' => ['max-age=86400', CheckStatus::Warn],
    'zero' => ['max-age=0', CheckStatus::Fail],
    'garbage' => ['nonsense', CheckStatus::Fail],
    'missing' => [null, CheckStatus::Fail],
]);

// CSP

test('csp', function (array $headers, string $head, CheckStatus $expected) {
    expect(runOneCheck(new CspCheck, siteContext(siteResponse(siteHtml($head), $headers)))->status)->toBe($expected);
})->with([
    'strict header' => [['Content-Security-Policy' => "default-src 'self'"], '', CheckStatus::Pass],
    'meta tag only' => [[], '<meta http-equiv="Content-Security-Policy" content="script-src \'self\'">', CheckStatus::Pass],
    'codelaunch.nl style: frame-ancestors header + meta' => [
        ['Content-Security-Policy' => "frame-ancestors 'none'"],
        '<meta http-equiv="content-security-policy" content="default-src \'self\'; script-src \'self\'">',
        CheckStatus::Pass,
    ],
    'nonce cancels unsafe-inline' => [['Content-Security-Policy' => "script-src 'nonce-abc' 'unsafe-inline'"], '', CheckStatus::Pass],
    'unsafe-inline' => [['Content-Security-Policy' => "script-src 'self' 'unsafe-inline'"], '', CheckStatus::Warn],
    'unsafe-eval' => [['Content-Security-Policy' => "default-src 'self' 'unsafe-eval'"], '', CheckStatus::Warn],
    'only frame-ancestors' => [['Content-Security-Policy' => "frame-ancestors 'none'"], '', CheckStatus::Warn],
    'report-only' => [['Content-Security-Policy-Report-Only' => "default-src 'self'"], '', CheckStatus::Warn],
    'none' => [[], '', CheckStatus::Fail],
]);

test('csp: a strict meta policy wins over a loose header, as in browsers', function () {
    $context = siteContext(siteResponse(
        siteHtml('<meta http-equiv="Content-Security-Policy" content="script-src \'self\'">'),
        ['Content-Security-Policy' => "script-src 'self' 'unsafe-inline'"],
    ));

    expect(runOneCheck(new CspCheck, $context)->status)->toBe(CheckStatus::Pass);
});

// Other security headers

test('security headers', function () {
    $all = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=()',
    ];

    $missingReferrer = $all;
    unset($missingReferrer['Referrer-Policy']);

    $frameAncestorsInstead = [...$all, 'Content-Security-Policy' => "frame-ancestors 'self'"];
    unset($frameAncestorsInstead['X-Frame-Options']);

    $noFraming = $all;
    unset($noFraming['X-Frame-Options']);

    expect(runOneCheck(new SecurityHeadersCheck, siteContext(siteResponse(headers: $all)))->status)->toBe(CheckStatus::Pass)
        ->and(runOneCheck(new SecurityHeadersCheck, siteContext(siteResponse(headers: $frameAncestorsInstead)))->status)->toBe(CheckStatus::Pass)
        ->and(runOneCheck(new SecurityHeadersCheck, siteContext(siteResponse(headers: $missingReferrer)))->status)->toBe(CheckStatus::Warn)
        ->and(runOneCheck(new SecurityHeadersCheck, siteContext(siteResponse(headers: $noFraming)))->status)->toBe(CheckStatus::Fail)
        ->and(runOneCheck(new SecurityHeadersCheck, siteContext(siteResponse()))->details)->toHaveCount(4);
});

// Title and description

test('title and description', function (string $head, CheckStatus $expected) {
    expect(runOneCheck(new TitleDescriptionCheck, siteContext(siteResponse(siteHtml($head))))->status)->toBe($expected);
})->with([
    'both good' => ['<title>Bakkerij de Vries</title><meta name="description" content="Fresh bread every morning, ordered online before 8 pm and ready at 7.">', CheckStatus::Pass],
    'short title' => ['<title>Home</title><meta name="description" content="Fresh bread every morning, ordered online before 8 pm and ready at 7.">', CheckStatus::Warn],
    'short description' => ['<title>Bakkerij de Vries</title><meta name="description" content="Bread.">', CheckStatus::Warn],
    'no description' => ['<title>Bakkerij de Vries</title>', CheckStatus::Fail],
    'no title' => ['<meta name="description" content="Fresh bread every morning, ordered online before 8 pm and ready at 7.">', CheckStatus::Fail],
]);

test('html checks fail clearly when the page is not html or not found', function () {
    $pdf = siteContext(siteResponse('%PDF', ['Content-Type' => 'application/pdf']));
    $missing = siteContext(siteResponse(siteHtml(), status: 404));

    expect(runOneCheck(new TitleDescriptionCheck, $pdf)->message)->toContain("isn't HTML")
        ->and(runOneCheck(new HeadingsCheck, $missing)->message)->toContain('HTTP 404');
});

// Open Graph image

test('og:image is set and loads', function () {
    $page = siteResponse(siteHtml('<meta property="og:image" content="https://site.example/og.jpg">'));
    $context = siteContext($page, ['HEAD https://site.example/og.jpg' => siteResponse(headers: ['Content-Type' => 'image/jpeg'], url: 'https://site.example/og.jpg')]);

    expect(runOneCheck(new OpenGraphImageCheck, $context)->status)->toBe(CheckStatus::Pass);
});

test('og:image problems', function () {
    $missing = siteContext(siteResponse(siteHtml()));
    $broken = siteContext(
        siteResponse(siteHtml('<meta property="og:image" content="https://site.example/gone.jpg">')),
        ['HEAD https://site.example/gone.jpg' => siteResponse(status: 404, url: 'https://site.example/gone.jpg')],
    );
    $relative = siteContext(
        siteResponse(siteHtml('<meta property="og:image" content="/og.jpg">')),
        ['HEAD https://site.example/og.jpg' => siteResponse(headers: ['Content-Type' => 'image/png'], url: 'https://site.example/og.jpg')],
    );

    expect(runOneCheck(new OpenGraphImageCheck, $missing)->status)->toBe(CheckStatus::Fail)
        ->and(runOneCheck(new OpenGraphImageCheck, $broken)->status)->toBe(CheckStatus::Fail)
        ->and(runOneCheck(new OpenGraphImageCheck, $relative)->status)->toBe(CheckStatus::Warn);
});

// Image alt text

test('image alt coverage', function () {
    $good = siteContext(siteResponse(siteHtml(body: '<h1>A</h1><img src="a.png" alt="A cake"><img src="line.svg" alt="">')));
    $bad = siteContext(siteResponse(siteHtml(body: '<img src="/img/hero.jpg"><img src="b.png" alt="B">')));
    $none = siteContext(siteResponse(siteHtml()));

    $result = runOneCheck(new ImageAltCheck, $bad);

    expect(runOneCheck(new ImageAltCheck, $good)->message)->toBe('All 2 images have alt text (1 marked decorative).')
        ->and($result->status)->toBe(CheckStatus::Fail)
        ->and($result->message)->toBe('1 of 2 images have no alt attribute.')
        ->and($result->details)->toBe(['hero.jpg'])
        ->and(runOneCheck(new ImageAltCheck, $none)->status)->toBe(CheckStatus::Pass);
});

// Headings

test('headings', function (string $body, CheckStatus $expected) {
    expect(runOneCheck(new HeadingsCheck, siteContext(siteResponse(siteHtml(body: $body))))->status)->toBe($expected);
})->with([
    'logical' => ['<h1>A</h1><h2>B</h2><h3>C</h3><h2>D</h2>', CheckStatus::Pass],
    'no h1' => ['<h2>B</h2><h3>C</h3>', CheckStatus::Fail],
    'two h1s' => ['<h1>A</h1><h1>B</h1>', CheckStatus::Warn],
    'skipped level' => ['<h1>A</h1><h2>B</h2><h4>D</h4>', CheckStatus::Warn],
]);

test('headings name the skipped level', function () {
    $result = runOneCheck(new HeadingsCheck, siteContext(siteResponse(siteHtml(body: '<h1>A</h1><h2>Menu</h2><h4>Bread</h4>'))));

    expect($result->details)->toBe(['h2 → h4 (“Bread”)']);
});

// robots.txt and sitemap.xml

test('robots and sitemap', function () {
    $robots = siteResponse("User-agent: *\nAllow: /\nSitemap: https://site.example/map.xml", ['Content-Type' => 'text/plain'], url: 'https://site.example/robots.txt');
    $sitemap = siteResponse('<?xml version="1.0"?><urlset><url><loc>https://site.example/</loc></url></urlset>', ['Content-Type' => 'application/xml'], url: 'https://site.example/map.xml');

    $both = siteContext(siteResponse(), ['GET https://site.example/robots.txt' => $robots, 'GET https://site.example/map.xml' => $sitemap]);
    $neither = siteContext(siteResponse());
    $homePageEverywhere = siteContext(siteResponse(), [
        'GET https://site.example/robots.txt' => siteResponse(siteHtml(), ['Content-Type' => 'text/html']),
        'GET https://site.example/sitemap.xml' => siteResponse(siteHtml(), ['Content-Type' => 'text/html']),
    ]);

    expect(runOneCheck(new RobotsSitemapCheck, $both)->message)->toBe('Both are in place; the sitemap lists 1 URL.')
        ->and(runOneCheck(new RobotsSitemapCheck, $neither)->status)->toBe(CheckStatus::Fail)
        ->and(runOneCheck(new RobotsSitemapCheck, $homePageEverywhere)->status)->toBe(CheckStatus::Fail);
});

test('robots.txt that blocks every crawler fails', function (string $robots, bool $blocks) {
    expect(RobotsSitemapCheck::blocksEverything($robots))->toBe($blocks);
})->with([
    'staging leftover' => ["User-agent: *\nDisallow: /", true],
    'with comments' => ["# staging\nUser-agent: * # all\nDisallow: / # everything", true],
    'only one bot' => ["User-agent: BadBot\nDisallow: /\n\nUser-agent: *\nAllow: /", false],
    'one folder' => ["User-agent: *\nDisallow: /admin/", false],
    'empty disallow' => ["User-agent: *\nDisallow:", false],
    'shared group' => ["User-agent: Googlebot\nUser-agent: *\nDisallow: /", true],
]);

// Performance

test('response time and page weight', function (int $ms, int $bytes, CheckStatus $expected) {
    expect(runOneCheck(new PerformanceCheck, siteContext(siteResponse(str_repeat('a', $bytes), timeMs: $ms)))->status)->toBe($expected);
})->with([
    'fast and light' => [300, 30_000, CheckStatus::Pass],
    'slowish' => [1500, 30_000, CheckStatus::Warn],
    'slow' => [3000, 30_000, CheckStatus::Fail],
    'heavy' => [300, 700 * 1024, CheckStatus::Warn],
    'very heavy' => [300, 1100 * 1024, CheckStatus::Fail],
]);

// The runner

test('a fetch failure becomes a failed result with a plain message', function () {
    $context = CheckContext::fake(SITE, ['GET '.SITE => FetchFailed::unreachable("site.example didn't answer within 10 seconds.")]);

    $result = runOneCheck(new HstsCheck, $context);

    expect($result->status)->toBe(CheckStatus::Fail)
        ->and($result->message)->toBe("site.example didn't answer within 10 seconds.");
});

test('checks that do not get a turn within the budget are skipped', function () {
    $now = 0.0;
    $context = new CheckContext(SITE, null, 10.0, function () use (&$now): float {
        return $now;
    }, ['GET '.SITE => siteResponse()]);

    $slow = new class($now) implements LaunchCheck
    {
        public function __construct(private float &$now) {}

        public function key(): string
        {
            return 'slow';
        }

        public function label(): string
        {
            return 'Slow';
        }

        public function run(CheckContext $context): CheckResult
        {
            $this->now += 20;

            return CheckResult::pass('Took ages.');
        }
    };

    $results = (new CheckRunner(unusedFetcher()))->run(SITE, [$slow, new HstsCheck], $context);

    expect($results[0]['result']->status)->toBe(CheckStatus::Pass)
        ->and($results[1]['result']->status)->toBe(CheckStatus::Skipped);
});
