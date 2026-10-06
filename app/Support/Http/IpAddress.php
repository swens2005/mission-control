<?php

namespace App\Support\Http;

/**
 * Decides whether an IP address is on the public internet (ADR 0007).
 *
 * Two independent layers: PHP's own "global range" filter (RFC 6890), and an
 * explicit list of ranges below, so a gap in either is covered by the other.
 * IPv6 addresses that embed an IPv4 address (mapped, compatible, NAT64,
 * 6to4) are judged by the IPv4 address inside them.
 */
final class IpAddress
{
    /** Non-public IPv4 ranges. */
    private const BLOCKED_V4 = [
        '0.0.0.0/8',          // "this network", including 0.0.0.0
        '10.0.0.0/8',         // private
        '100.64.0.0/10',      // carrier-grade NAT
        '127.0.0.0/8',        // loopback
        '169.254.0.0/16',     // link-local, including cloud metadata
        '172.16.0.0/12',      // private
        '192.0.0.0/24',       // IETF protocol assignments
        '192.0.2.0/24',       // documentation
        '192.88.99.0/24',     // 6to4 relay
        '192.168.0.0/16',     // private
        '198.18.0.0/15',      // benchmarking
        '198.51.100.0/24',    // documentation
        '203.0.113.0/24',     // documentation
        '224.0.0.0/4',        // multicast
        '240.0.0.0/4',        // reserved, including broadcast
    ];

    /** Non-public IPv6 ranges (embedded IPv4 is unwrapped first). */
    private const BLOCKED_V6 = [
        '::/128',             // unspecified
        '::1/128',            // loopback
        '100::/64',           // discard
        '2001::/23',          // IETF protocol assignments, including Teredo
        '2001:db8::/32',      // documentation
        'fc00::/7',           // unique local (private)
        'fe80::/10',          // link-local
        'fec0::/10',          // site-local (deprecated)
        'ff00::/8',           // multicast
    ];

    public static function isPublic(string $ip): bool
    {
        $ip = trim($ip, '[]');

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $embedded = self::embeddedIpv4($ip);

        if ($embedded !== null) {
            return self::isPublic($embedded);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        $ranges = str_contains($ip, ':') ? self::BLOCKED_V6 : self::BLOCKED_V4;

        foreach ($ranges as $range) {
            if (self::inRange($ip, $range)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The IPv4 address hidden in an IPv4-mapped (::ffff:a.b.c.d),
     * IPv4-compatible (::a.b.c.d), NAT64 (64:ff9b::/96) or 6to4 (2002::/16)
     * IPv6 address, or null.
     */
    private static function embeddedIpv4(string $ip): ?string
    {
        if (! str_contains($ip, ':')) {
            return null;
        }

        $bytes = (string) inet_pton($ip);

        $prefix = static fn (string $hex): bool => str_starts_with(bin2hex($bytes), $hex);

        $v4 = match (true) {
            $prefix('00000000000000000000ffff') => substr($bytes, 12, 4),
            $prefix('000000000000000000000000') && substr($bytes, 12, 4) !== "\0\0\0\0" && substr($bytes, 12, 4) !== "\0\0\0\1" => substr($bytes, 12, 4),
            $prefix('0064ff9b0000000000000000') => substr($bytes, 12, 4),
            $prefix('2002') => substr($bytes, 2, 4),
            default => null,
        };

        return $v4 === null ? null : (string) inet_ntop($v4);
    }

    private static function inRange(string $ip, string $cidr): bool
    {
        [$network, $bits] = explode('/', $cidr);

        $ipBytes = (string) inet_pton($ip);
        $networkBytes = (string) inet_pton($network);

        if (strlen($ipBytes) !== strlen($networkBytes)) {
            return false;
        }

        $bits = (int) $bits;
        $fullBytes = intdiv($bits, 8);

        if (substr($ipBytes, 0, $fullBytes) !== substr($networkBytes, 0, $fullBytes)) {
            return false;
        }

        $remaining = $bits % 8;

        if ($remaining === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remaining)) & 0xFF;

        return (ord($ipBytes[$fullBytes]) & $mask) === (ord($networkBytes[$fullBytes]) & $mask);
    }
}
