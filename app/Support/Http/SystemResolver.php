<?php

namespace App\Support\Http;

/**
 * DNS through the host's own resolver: A records via gethostbynamel(),
 * AAAA records via dns_get_record() where the host allows it.
 */
final class SystemResolver implements Resolver
{
    public function resolve(string $host): array
    {
        $addresses = gethostbynamel($host) ?: [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_AAAA) ?: [];

            foreach ($records as $record) {
                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($addresses));
    }
}
