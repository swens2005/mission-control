<?php

namespace App\Support\Launch\Checks;

use App\Support\Http\FetchedResponse;
use App\Support\Http\FetchFailed;
use App\Support\Http\SafeFetcher;
use Closure;
use Dom\HTMLDocument;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

/**
 * What the checks share during one run: the site URL, a fetcher whose
 * results are remembered (the page is fetched once, not once per check),
 * the parsed page, and the time budget.
 */
final class CheckContext
{
    /** @var array<string, FetchedResponse|FetchFailed> */
    private array $fetched = [];

    private ?HTMLDocument $document = null;

    /**
     * @param  SafeFetcher|null  $fetcher  null in tests: only the fixtures are "online"
     * @param  Closure(): float  $clock  seconds, monotonic; replaceable in tests
     * @param  array<string, FetchedResponse|FetchFailed>  $fixtures  "GET https://..." => response
     */
    public function __construct(
        public readonly string $url,
        private readonly ?SafeFetcher $fetcher,
        private readonly float $deadline,
        private readonly Closure $clock,
        array $fixtures = [],
    ) {
        $this->fetched = $fixtures;
    }

    /**
     * A context that answers from fixtures only, for unit testing checks.
     *
     * @param  array<string, FetchedResponse|FetchFailed>  $fixtures  "GET https://..." => response
     */
    public static function fake(string $url, array $fixtures): self
    {
        return new self($url, null, PHP_FLOAT_MAX, static fn (): float => 0.0, $fixtures);
    }

    public static function start(string $url, SafeFetcher $fetcher, float $budgetSeconds): self
    {
        $clock = static fn (): float => hrtime(true) / 1e9;

        return new self($url, $fetcher, $clock() + $budgetSeconds, $clock);
    }

    public function remaining(): float
    {
        return $this->deadline - ($this->clock)();
    }

    /**
     * Fetches a URL once per run; later calls get the same answer, including
     * the same failure.
     *
     * @param  'GET'|'HEAD'  $method
     *
     * @throws FetchFailed
     * @throws BudgetExceeded
     */
    public function fetch(string $url, string $method = 'GET'): FetchedResponse
    {
        $key = "{$method} {$url}";

        if (! isset($this->fetched[$key])) {
            $remaining = $this->remaining();

            if ($remaining < 1) {
                throw new BudgetExceeded;
            }

            if ($this->fetcher === null) {
                throw FetchFailed::unreachable("{$url} isn't in the test fixtures.");
            }

            try {
                $this->fetched[$key] = $this->fetcher->fetch($url, $method, $remaining);
            } catch (FetchFailed $e) {
                $this->fetched[$key] = $e;
            }
        }

        $result = $this->fetched[$key];

        if ($result instanceof FetchFailed) {
            throw $result;
        }

        return $result;
    }

    /**
     * The site's page, as the launch URL serves it (redirects followed).
     *
     * @throws FetchFailed
     * @throws BudgetExceeded
     */
    public function page(): FetchedResponse
    {
        return $this->fetch($this->url);
    }

    /**
     * The page parsed as HTML5.
     *
     * @throws FetchFailed when the page can't be fetched, isn't a success or isn't HTML
     * @throws BudgetExceeded
     */
    public function document(): HTMLDocument
    {
        if ($this->document !== null) {
            return $this->document;
        }

        $page = $this->page();

        if (! $page->successful()) {
            throw FetchFailed::unreachable("The page answered with HTTP {$page->status}.");
        }

        $type = strtolower((string) $page->header('content-type'));

        if ($type !== '' && ! str_contains($type, 'html')) {
            throw FetchFailed::unreachable("The page isn't HTML ({$type}).");
        }

        return $this->document = HTMLDocument::createFromString($page->body, LIBXML_NOERROR);
    }

    /**
     * An address relative to the page's final URL, made absolute.
     *
     * @throws FetchFailed
     * @throws BudgetExceeded
     */
    public function absolute(string $reference): string
    {
        return (string) UriResolver::resolve(new Uri($this->page()->url), new Uri($reference));
    }

    /**
     * scheme://host of the page's final URL, for /robots.txt and friends.
     *
     * @throws FetchFailed
     * @throws BudgetExceeded
     */
    public function origin(): string
    {
        $uri = new Uri($this->page()->url);

        return $uri->getScheme().'://'.$uri->getAuthority();
    }
}
