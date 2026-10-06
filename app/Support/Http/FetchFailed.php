<?php

namespace App\Support\Http;

use RuntimeException;

/**
 * A fetch that SafeFetcher refused or couldn't complete. The message is
 * plain English and safe to show the user: it never includes the response.
 */
final class FetchFailed extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function blocked(string $message): self
    {
        return new self($message, 'blocked');
    }

    public static function unreachable(string $message): self
    {
        return new self($message, 'unreachable');
    }

    public static function tooLarge(): self
    {
        return new self('The response was larger than 2 MB, so it was not downloaded.', 'too_large');
    }
}
