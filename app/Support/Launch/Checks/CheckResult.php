<?php

namespace App\Support\Launch\Checks;

use App\Enums\CheckStatus;

/**
 * What one check found: a status, a one-line message and optional details.
 */
final readonly class CheckResult
{
    /**
     * @param  list<string>  $details
     */
    public function __construct(
        public CheckStatus $status,
        public string $message,
        public array $details = [],
    ) {}

    /** @param list<string> $details */
    public static function pass(string $message, array $details = []): self
    {
        return new self(CheckStatus::Pass, $message, $details);
    }

    /** @param list<string> $details */
    public static function warn(string $message, array $details = []): self
    {
        return new self(CheckStatus::Warn, $message, $details);
    }

    /** @param list<string> $details */
    public static function fail(string $message, array $details = []): self
    {
        return new self(CheckStatus::Fail, $message, $details);
    }

    public static function skipped(string $message): self
    {
        return new self(CheckStatus::Skipped, $message);
    }
}
