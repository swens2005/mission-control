<?php

namespace App\Support\Http;

/**
 * What SafeFetcher hands to the launch checks.
 */
final readonly class FetchedResponse
{
    /**
     * @param  array<string, list<string>>  $headers  lower-case names
     * @param  list<array{url: string, status: int}>  $redirects  each hop before the final URL
     */
    public function __construct(
        public string $url,
        public int $status,
        public array $headers,
        public string $body,
        public int $timeMs,
        public array $redirects = [],
    ) {}

    public function header(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? [];

        return $values === [] ? null : implode(', ', $values);
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
