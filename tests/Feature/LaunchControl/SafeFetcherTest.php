<?php

use App\Support\Http\FetchFailed;
use App\Support\Http\Resolver;
use App\Support\Http\SafeFetcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * DNS answers for the tests; the real network is never touched.
 *
 * @param  array<string, list<string>>  $records
 */
function fetcherWithDns(array $records): SafeFetcher
{
    $resolver = new class($records) implements Resolver
    {
        /** @param array<string, list<string>> $records */
        public function __construct(private array $records) {}

        public function resolve(string $host): array
        {
            return $this->records[$host] ?? [];
        }
    };

    return new SafeFetcher($resolver);
}

function fetchFails(callable $fetch): FetchFailed
{
    try {
        $fetch();
    } catch (FetchFailed $e) {
        return $e;
    }

    throw new RuntimeException('Expected the fetch to fail.');
}

beforeEach(function () {
    Http::preventStrayRequests();
});

test('fetches a public site and reports status, headers and body', function () {
    Http::fake(['https://site.example/*' => Http::response('<h1>Hi</h1>', 200, ['Content-Type' => 'text/html', 'X-Frame-Options' => 'DENY'])]);

    $response = fetcherWithDns(['site.example' => ['93.184.216.34']])->fetch('https://site.example/');

    expect($response->status)->toBe(200)
        ->and($response->body)->toBe('<h1>Hi</h1>')
        ->and($response->header('x-frame-options'))->toBe('DENY')
        ->and($response->url)->toBe('https://site.example/')
        ->and($response->redirects)->toBe([]);

    Http::assertSent(fn (Request $request) => $request->hasHeader('User-Agent', SafeFetcher::USER_AGENT));
});

test('refuses addresses that are not plain http(s) on standard ports', function (string $url) {
    $e = fetchFails(fn () => fetcherWithDns(['site.example' => ['93.184.216.34']])->fetch($url));

    expect($e->reason)->toBe('blocked');
    Http::assertNothingSent();
})->with([
    'ftp' => 'ftp://site.example/',
    'file' => 'file:///etc/passwd',
    'gopher' => 'gopher://site.example/',
    'javascript' => 'javascript:alert(1)',
    'other port' => 'https://site.example:8443/',
    'ssh port' => 'http://site.example:22/',
    'credentials' => 'https://user:secret@site.example/',
    'no host' => 'https:///path',
    'garbage' => 'not a url',
]);

test('refuses hosts that are, or resolve to, non-public addresses', function (string $url, array $dns) {
    $e = fetchFails(fn () => fetcherWithDns($dns)->fetch($url));

    expect($e->reason)->toBe('blocked');
    Http::assertNothingSent();
})->with([
    'loopback literal' => ['http://127.0.0.1/', []],
    'unspecified literal' => ['http://0.0.0.0/', []],
    'metadata literal' => ['http://169.254.169.254/latest/meta-data/', []],
    'IPv6 loopback literal' => ['http://[::1]/', []],
    'IPv4-mapped literal' => ['http://[::ffff:127.0.0.1]/', []],
    'decimal IP' => ['http://2130706433/', []],
    'octal IP' => ['http://0177.0.0.1/', []],
    'short IP' => ['http://127.1/', []],
    'hex IP' => ['http://0x7f000001/', []],
    'localhost' => ['http://localhost/', ['localhost' => ['127.0.0.1']]],
    'private DNS answer' => ['https://intranet.example/', ['intranet.example' => ['10.1.2.3']]],
    'one bad answer among good ones' => ['https://mixed.example/', ['mixed.example' => ['93.184.216.34', '192.168.0.10']]],
    'private IPv6 answer' => ['https://v6.example/', ['v6.example' => ['fd00::1']]],
]);

test('a host that does not resolve is unreachable, not blocked', function () {
    $e = fetchFails(fn () => fetcherWithDns([])->fetch('https://nowhere.example/'));

    expect($e->reason)->toBe('unreachable')
        ->and($e->getMessage())->toContain("couldn't be found");
});

test('follows a redirect after checking the new host', function () {
    Http::fake([
        'http://site.example/*' => Http::response('', 301, ['Location' => 'https://www.site.example/home']),
        'https://www.site.example/*' => Http::response('welcome'),
    ]);

    $response = fetcherWithDns([
        'site.example' => ['93.184.216.34'],
        'www.site.example' => ['93.184.216.35'],
    ])->fetch('http://site.example/');

    expect($response->url)->toBe('https://www.site.example/home')
        ->and($response->body)->toBe('welcome')
        ->and($response->redirects)->toBe([['url' => 'http://site.example/', 'status' => 301]]);
});

test('resolves relative redirects against the current address', function () {
    Http::fake([
        'https://site.example/old' => Http::response('', 302, ['Location' => '/new']),
        'https://site.example/new' => Http::response('moved'),
    ]);

    $response = fetcherWithDns(['site.example' => ['93.184.216.34']])->fetch('https://site.example/old');

    expect($response->url)->toBe('https://site.example/new');
});

test('refuses a redirect to a private address', function () {
    Http::fake(['https://site.example/*' => Http::response('', 302, ['Location' => 'http://internal.example/admin'])]);

    $e = fetchFails(fn () => fetcherWithDns([
        'site.example' => ['93.184.216.34'],
        'internal.example' => ['10.0.0.5'],
    ])->fetch('https://site.example/'));

    expect($e->reason)->toBe('blocked');
    Http::assertSentCount(1);
});

test('refuses a redirect to a metadata IP literal', function () {
    Http::fake(['https://site.example/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/'])]);

    $e = fetchFails(fn () => fetcherWithDns(['site.example' => ['93.184.216.34']])->fetch('https://site.example/'));

    expect($e->reason)->toBe('blocked');
    Http::assertSentCount(1);
});

test('stops a redirect loop after five redirects', function () {
    Http::fake(['https://loop.example/*' => Http::response('', 302, ['Location' => '/again'])]);

    $e = fetchFails(fn () => fetcherWithDns(['loop.example' => ['93.184.216.34']])->fetch('https://loop.example/'));

    expect($e->getMessage())->toContain('more than 5 times');
    Http::assertSentCount(6);
});

test('refuses a response over 2 MB', function () {
    Http::fake(['https://big.example/*' => Http::response(str_repeat('a', SafeFetcher::MAX_BYTES + 1))]);

    $e = fetchFails(fn () => fetcherWithDns(['big.example' => ['93.184.216.34']])->fetch('https://big.example/'));

    expect($e->reason)->toBe('too_large');
});

test('unpacks gzip, but refuses one that unpacks to over 2 MB', function () {
    Http::fake([
        'https://small.example/*' => Http::response((string) gzencode('<title>Small</title>'), 200, ['Content-Encoding' => 'gzip']),
        'https://bomb.example/*' => Http::response((string) gzencode(str_repeat('a', 3 * 1024 * 1024)), 200, ['Content-Encoding' => 'gzip']),
    ]);

    $fetcher = fetcherWithDns(['small.example' => ['93.184.216.34'], 'bomb.example' => ['93.184.216.34']]);

    expect($fetcher->fetch('https://small.example/')->body)->toBe('<title>Small</title>')
        ->and(fetchFails(fn () => $fetcher->fetch('https://bomb.example/'))->reason)->toBe('too_large');
});

test('explains connection failures in plain words', function (string $curlError, string $expected) {
    Http::fake(fn () => throw new ConnectionException($curlError));

    $e = fetchFails(fn () => fetcherWithDns(['slow.example' => ['93.184.216.34']])->fetch('https://slow.example/'));

    expect($e->reason)->toBe('unreachable')
        ->and($e->getMessage())->toContain($expected)
        ->and($e->getMessage())->not->toContain('cURL');
})->with([
    ['cURL error 28: Operation timed out after 10001 milliseconds', "didn't answer within 10 seconds"],
    ['cURL error 7: Failed to connect to 93.184.216.34 port 443', 'refused the connection'],
    ['cURL error 60: SSL certificate problem', 'certificate'],
]);

test('pins the connection to the checked IPv4 address first', function () {
    $target = fetcherWithDns(['dual.example' => ['2606:4700::1', '93.184.216.34']])->check('https://dual.example/');

    expect($target)->toBe(['host' => 'dual.example', 'port' => 443, 'ip' => '93.184.216.34', 'literal' => false])
        ->and(SafeFetcher::resolveEntry('dual.example', 443, '93.184.216.34'))->toBe('dual.example:443:93.184.216.34')
        ->and(SafeFetcher::resolveEntry('v6.example', 80, '2606:4700::1'))->toBe('v6.example:80:[2606:4700::1]');
});
