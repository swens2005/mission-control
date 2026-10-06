<?php

use App\Support\Http\IpAddress;

test('public addresses are allowed', function (string $ip) {
    expect(IpAddress::isPublic($ip))->toBeTrue();
})->with([
    '8.8.8.8',
    '1.1.1.1',
    '185.199.108.153',
    '2606:4700:4700::1111',
    '2a00:1450:400e:80c::200e',
    '[2606:4700:4700::1111]',
]);

test('non-public addresses are blocked', function (string $ip) {
    expect(IpAddress::isPublic($ip))->toBeFalse();
})->with([
    'unspecified' => '0.0.0.0',
    'this network' => '0.1.2.3',
    'private 10/8' => '10.0.0.1',
    'private 172.16/12' => '172.16.5.4',
    'private 172.31' => '172.31.255.255',
    'private 192.168/16' => '192.168.1.1',
    'loopback' => '127.0.0.1',
    'loopback range' => '127.255.0.1',
    'cloud metadata' => '169.254.169.254',
    'carrier-grade NAT' => '100.64.0.1',
    'documentation' => '192.0.2.10',
    'benchmarking' => '198.18.0.1',
    'multicast' => '224.0.0.1',
    'reserved' => '240.0.0.1',
    'broadcast' => '255.255.255.255',
    'IPv6 unspecified' => '::',
    'IPv6 loopback' => '::1',
    'IPv6 loopback in brackets' => '[::1]',
    'IPv6 unique local' => 'fd12:3456:789a::1',
    'IPv6 link-local' => 'fe80::1',
    'IPv6 site-local' => 'fec0::1',
    'IPv6 multicast' => 'ff02::1',
    'IPv6 documentation' => '2001:db8::1',
    'Teredo' => '2001:0:4136:e378::1',
    'IPv4-mapped loopback' => '::ffff:127.0.0.1',
    'IPv4-mapped metadata' => '::ffff:169.254.169.254',
    'IPv4-mapped private, hex form' => '::ffff:a00:1',
    'IPv4-compatible loopback' => '::127.0.0.1',
    'NAT64 private' => '64:ff9b::10.0.0.1',
    '6to4 private' => '2002:c0a8:0101::1',
    'octal form' => '0177.0.0.1',
    'decimal form' => '2130706433',
    'not an address' => 'example.com',
    'empty' => '',
]);

test('embedded public IPv4 addresses are allowed', function () {
    expect(IpAddress::isPublic('::ffff:8.8.8.8'))->toBeTrue()
        ->and(IpAddress::isPublic('64:ff9b::8.8.8.8'))->toBeTrue();
});
