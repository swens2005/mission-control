<?php

namespace App\Support\Http;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The only way the app fetches a user-supplied URL (ADR 0007).
 *
 * For every hop, redirects included: http(s) on ports 80/443 only, resolve
 * the host, refuse it if any address isn't public, then connect to exactly
 * the address that was checked (CURLOPT_RESOLVE), so DNS can't change in
 * between. Redirects are followed by hand, at most 5. Each request is capped
 * at 5 s to connect, 10 s in total and 2 MB of body, also after unzipping.
 */
final class SafeFetcher
{
    public const MAX_REDIRECTS = 5;

    public const MAX_BYTES = 2 * 1024 * 1024;

    public const CONNECT_TIMEOUT = 5;

    public const TIMEOUT = 10;

    public const USER_AGENT = 'MissionControl-LaunchCheck/1.0 (+https://codelaunch.nl/mission-control)';

    public function __construct(private readonly Resolver $resolver) {}

    /**
     * @param  'GET'|'HEAD'  $method
     *
     * @throws FetchFailed
     */
    public function fetch(string $url, string $method = 'GET'): FetchedResponse
    {
        // Without curl, Guzzle would fall back to a handler that can't be
        // pinned to the checked address. Fail closed instead.
        if (! extension_loaded('curl')) {
            throw FetchFailed::unreachable('This server cannot make safe outside requests (curl is missing).');
        }

        $redirects = [];
        $current = $url;

        for ($hop = 0; ; $hop++) {
            $target = $this->check($current);
            $started = hrtime(true);
            $response = $this->request($current, $method, $target);
            $timeMs = (int) round((hrtime(true) - $started) / 1_000_000);

            $location = $response->header('Location');

            if ($response->status() >= 300 && $response->status() < 400 && $location !== '') {
                if ($hop >= self::MAX_REDIRECTS) {
                    throw FetchFailed::blocked('The address redirected more than '.self::MAX_REDIRECTS.' times, so it was not followed further.');
                }

                $redirects[] = ['url' => $current, 'status' => $response->status()];
                $current = (string) UriResolver::resolve(new Uri($current), new Uri($location));

                continue;
            }

            return new FetchedResponse(
                url: $current,
                status: $response->status(),
                headers: array_change_key_case($response->headers(), CASE_LOWER),
                body: $method === 'HEAD' ? '' : $this->decode($response),
                timeMs: $timeMs,
                redirects: $redirects,
            );
        }
    }

    /**
     * Validates one URL and returns where to connect.
     *
     * @return array{host: string, port: int, ip: string, literal: bool}
     *
     * @throws FetchFailed
     */
    public function check(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw FetchFailed::blocked("That isn't a complete web address.");
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw FetchFailed::blocked('Only http and https addresses can be checked.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw FetchFailed::blocked("Addresses with a username or password can't be checked.");
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, [80, 443], true)) {
            throw FetchFailed::blocked('Only the standard web ports (80 and 443) can be checked.');
        }

        $host = rtrim(strtolower($parts['host']), '.');
        [$addresses, $literal] = $this->addressesFor($host);

        foreach ($addresses as $address) {
            if (! IpAddress::isPublic($address)) {
                throw FetchFailed::blocked("{$host} points to a private or reserved network address, which can't be checked.");
            }
        }

        $ipv4 = array_values(array_filter($addresses, fn (string $address) => ! str_contains($address, ':')));

        return ['host' => $host, 'port' => $port, 'ip' => $ipv4[0] ?? $addresses[0], 'literal' => $literal];
    }

    /**
     * The curl --resolve entry that pins host:port to the checked address.
     */
    public static function resolveEntry(string $host, int $port, string $ip): string
    {
        return "{$host}:{$port}:".(str_contains($ip, ':') ? "[{$ip}]" : $ip);
    }

    /**
     * @return array{0: non-empty-list<string>, 1: bool} the addresses, and whether the host was an IP literal
     *
     * @throws FetchFailed
     */
    private function addressesFor(string $host): array
    {
        if (str_starts_with($host, '[')) {
            $ip = trim($host, '[]');

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                throw FetchFailed::blocked("That address isn't valid.");
            }

            return [[$ip], true];
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return [[$host], true];
        }

        if (preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*$/', $host) !== 1) {
            throw FetchFailed::blocked("That host name isn't valid. International names need their xn-- form.");
        }

        // Real top-level domains are never numbers. A numeric last part means
        // an IP address in disguise (2130706433, 0x7f.1, 0177.0.0.1).
        $labels = explode('.', $host);

        if (preg_match('/^(\d+|0x[0-9a-f]*)$/', end($labels)) === 1) {
            throw FetchFailed::blocked('Write IP addresses in the usual dotted form, or use the domain name.');
        }

        $addresses = $this->resolver->resolve($host);

        if ($addresses === []) {
            throw FetchFailed::unreachable("{$host} couldn't be found in DNS.");
        }

        return [$addresses, false];
    }

    /**
     * @param  array{host: string, port: int, ip: string, literal: bool}  $target
     *
     * @throws FetchFailed
     */
    private function request(string $url, string $method, array $target): Response
    {
        $tooLarge = false;

        $options = [
            'allow_redirects' => false,
            'protocols' => ['http', 'https'],
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout' => self::TIMEOUT,
            // Unzipped by decode() with a size cap, never by curl.
            'decode_content' => false,
            // Returning true aborts the transfer as soon as it passes the cap.
            'progress' => function (int $downloadTotal, int $downloaded) use (&$tooLarge): bool {
                $tooLarge = $downloadTotal > self::MAX_BYTES || $downloaded > self::MAX_BYTES;

                return $tooLarge;
            },
        ];

        if (! $target['literal']) {
            $options['curl'] = [CURLOPT_RESOLVE => [self::resolveEntry($target['host'], $target['port'], $target['ip'])]];
        }

        try {
            $response = Http::withOptions($options)->withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                'Accept-Encoding' => 'gzip',
            ])->send($method, $url);
        } catch (ConnectionException $e) {
            throw $tooLarge ? FetchFailed::tooLarge() : FetchFailed::unreachable(self::describe($e, $target['host']));
        } catch (Throwable $e) {
            report($e);

            throw $tooLarge ? FetchFailed::tooLarge() : FetchFailed::unreachable("Couldn't fetch {$target['host']}.");
        }

        if ($tooLarge || strlen($response->body()) > self::MAX_BYTES) {
            throw FetchFailed::tooLarge();
        }

        return $response;
    }

    /**
     * The body as text: gzip is unpacked here with the same 2 MB cap, so a
     * small compressed response can't expand into something huge.
     *
     * @throws FetchFailed
     */
    private function decode(Response $response): string
    {
        $body = $response->body();

        if (! str_contains(strtolower($response->header('Content-Encoding')), 'gzip') || $body === '') {
            return $body;
        }

        $decoded = @gzdecode($body, self::MAX_BYTES);

        if ($decoded === false) {
            throw new FetchFailed("The compressed response couldn't be unpacked within 2 MB.", 'too_large');
        }

        return $decoded;
    }

    /**
     * A plain-English reason from curl's error number, without echoing the
     * low-level message (which can include internal details).
     */
    private static function describe(ConnectionException $e, string $host): string
    {
        $errno = preg_match('/cURL error (\d+)/', $e->getMessage(), $matches) === 1 ? (int) $matches[1] : 0;

        return match ($errno) {
            28 => "{$host} didn't answer within ".self::TIMEOUT.' seconds.',
            6 => "{$host} couldn't be found in DNS.",
            7 => "{$host} refused the connection, or this server isn't allowed to connect out.",
            35, 60 => "{$host}'s HTTPS certificate couldn't be verified.",
            default => "Couldn't connect to {$host}.",
        };
    }
}
