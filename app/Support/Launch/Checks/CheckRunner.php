<?php

namespace App\Support\Launch\Checks;

use App\Support\Http\FetchFailed;
use App\Support\Http\SafeFetcher;
use Throwable;

/**
 * Runs every check against a site, synchronously (the host has no queue
 * worker, ADR 0002), within one shared time budget. Checks that don't get
 * a turn are "skipped" rather than left hanging.
 */
final class CheckRunner
{
    public const BUDGET_SECONDS = 25;

    public function __construct(private readonly SafeFetcher $fetcher) {}

    /**
     * @param  list<LaunchCheck>|null  $checks  defaults to every registered check
     * @return list<array{check: LaunchCheck, result: CheckResult}>
     */
    public function run(string $url, ?array $checks = null, ?CheckContext $context = null): array
    {
        $context ??= CheckContext::start($url, $this->fetcher, self::BUDGET_SECONDS);
        $results = [];

        foreach ($checks ?? CheckRegistry::all() as $check) {
            $results[] = ['check' => $check, 'result' => $this->runOne($check, $context)];
        }

        return $results;
    }

    private function runOne(LaunchCheck $check, CheckContext $context): CheckResult
    {
        if ($context->remaining() < 1) {
            return CheckResult::skipped('Skipped: the run used up its '.self::BUDGET_SECONDS.' second time budget.');
        }

        try {
            return $check->run($context);
        } catch (BudgetExceeded) {
            return CheckResult::skipped('Skipped: the run used up its '.self::BUDGET_SECONDS.' second time budget.');
        } catch (FetchFailed $e) {
            return CheckResult::fail($e->getMessage());
        } catch (Throwable $e) {
            // A bug in one check must not stop the others.
            report($e);

            return CheckResult::fail('This check hit an unexpected error.');
        }
    }
}
