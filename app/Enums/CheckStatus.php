<?php

namespace App\Enums;

/**
 * The outcome of one automated launch check.
 */
enum CheckStatus: string
{
    case Pass = 'pass';
    case Warn = 'warn';
    case Fail = 'fail';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Passed',
            self::Warn => 'Warning',
            self::Fail => 'Failed',
            self::Skipped => 'Skipped',
        };
    }

    /**
     * The worse of two outcomes (pass < warn < fail), for checks that look
     * at several things.
     */
    public function worst(self $other): self
    {
        $rank = [self::Pass->value => 0, self::Skipped->value => 1, self::Warn->value => 2, self::Fail->value => 3];

        return $rank[$other->value] > $rank[$this->value] ? $other : $this;
    }
}
