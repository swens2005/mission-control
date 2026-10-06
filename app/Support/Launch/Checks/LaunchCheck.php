<?php

namespace App\Support\Launch\Checks;

use App\Support\Http\FetchFailed;

/**
 * One automated pre-flight check. Each is small, reads what it needs from
 * the shared CheckContext, and is unit tested with fixture responses.
 */
interface LaunchCheck
{
    /** Stable identifier, stored with results and waivers. */
    public function key(): string;

    public function label(): string;

    /**
     * @throws FetchFailed when a page it needs can't be fetched; the runner
     *                     turns that into a failed result
     * @throws BudgetExceeded when the run is out of time
     */
    public function run(CheckContext $context): CheckResult;
}
