<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TEMPORARY diagnostic (Phase 2): can the host make outbound HTTP(S)
 * requests, and does curl's CURLOPT_RESOLVE pinning work there?
 *
 * Only fixed URLs, never user input. Same token and 404 rule as the deploy
 * endpoint. Removed in the commit after its first production run.
 */
final class OutboundCheckController
{
    private const TARGETS = [
        'https://codelaunch.nl/robots.txt',
        'http://codelaunch.nl/',
        'https://example.com/',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('app.deploy_token');
        abort_if($expected === '' || ! hash_equals($expected, (string) $request->bearerToken()), 404);

        $functions = ['curl_init', 'curl_exec', 'curl_multi_exec', 'gethostbynamel', 'dns_get_record', 'proc_open', 'fsockopen', 'stream_socket_client'];

        return response()->json([
            'php' => PHP_VERSION,
            'curl' => extension_loaded('curl') ? curl_version() : null,
            'functions' => collect($functions)->mapWithKeys(fn (string $f) => [$f => function_exists($f)])->all(),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'max_execution_time' => ini_get('max_execution_time'),
            'targets' => array_map($this->probe(...), self::TARGETS),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function probe(string $url): array
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_SCHEME) === 'https' ? 443 : 80;

        $started = microtime(true);
        $ipv4 = function_exists('gethostbynamel') ? (gethostbynamel($host) ?: []) : [];
        $aaaa = function_exists('dns_get_record') ? (@dns_get_record($host, DNS_AAAA) ?: []) : [];
        $dnsMs = (int) round((microtime(true) - $started) * 1000);

        $result = [
            'url' => $url,
            'dns_ms' => $dnsMs,
            'ipv4' => $ipv4,
            'ipv6' => array_column($aaaa, 'ipv6'),
        ];

        if (! function_exists('curl_init') || $ipv4 === []) {
            return $result;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ipv4[0]}"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'MissionControl-OutboundCheck/1.0',
        ]);
        $body = curl_exec($curl);

        return $result + [
            'pinned_to' => $ipv4[0],
            'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
            'primary_ip' => curl_getinfo($curl, CURLINFO_PRIMARY_IP),
            'redirect_to' => curl_getinfo($curl, CURLINFO_REDIRECT_URL),
            'total_ms' => (int) round(curl_getinfo($curl, CURLINFO_TOTAL_TIME) * 1000),
            'bytes' => is_string($body) ? strlen($body) : 0,
            'errno' => curl_errno($curl),
            'error' => curl_error($curl),
        ];
    }
}
