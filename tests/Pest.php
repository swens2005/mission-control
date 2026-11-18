<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BrowserTestCase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// The autopilot tours in a real browser (story 33). Not in the default
// suites; run them with `composer test:browser` after `npm run build`.
pest()->extend(BrowserTestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

/**
 * Every axe violation on the page, at any level, one line each (story 35;
 * shared by the browser tests).
 *
 * @return list<string>
 */
function axeViolations(mixed $page, string $name): array
{
    $violations = $page->script(<<<'JS'
        (async () => (await window.axe.run()).violations
            .map((v) => `${v.id}: ${v.help} -> ${v.nodes.map((n) => n.target.join(' ')).slice(0, 5).join(' | ')}`))()
        JS);

    return array_map(fn (string $line) => "[{$name}] {$line}", is_array($violations) ? $violations : []);
}
