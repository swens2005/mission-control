<?php

namespace App\Support\Http;

/**
 * Looks up a hostname's addresses. Behind an interface so tests never touch
 * the network.
 */
interface Resolver
{
    /**
     * Every IPv4 and IPv6 address the host resolves to; empty if none.
     *
     * @return list<string>
     */
    public function resolve(string $host): array;
}
