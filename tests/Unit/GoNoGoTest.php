<?php

use App\Enums\ChecklistOwner;
use App\Enums\CheckStatus;
use App\Support\Launch\Board;
use App\Support\Launch\GoNoGo;

/**
 * A board from short descriptions of each input.
 *
 * @param  array<string, CheckStatus>|null  $checks
 * @param  list<string>  $waived
 * @param  list<array{0: ChecklistOwner, 1: bool}>  $items
 */
function board(?array $checks, array $waived = [], array $items = [], ?string $studio = null, ?string $client = null, bool $current = true): Board
{
    return GoNoGo::evaluate(
        checksByKey: $checks,
        waived: $waived,
        checksCurrent: $current,
        checklist: array_map(fn (array $item) => ['owner' => $item[0], 'checked' => $item[1]], $items),
        studioSigner: $studio,
        clientSigner: $client,
    );
}

function allPassing(): array
{
    return ['https' => CheckStatus::Pass, 'hsts' => CheckStatus::Pass, 'csp' => CheckStatus::Pass];
}

function allTicked(): array
{
    return [[ChecklistOwner::Studio, true], [ChecklistOwner::Client, true]];
}

test('GO needs clear checks, a done checklist and both signatures', function () {
    $board = board(allPassing(), items: allTicked(), studio: 'Sam Visser', client: 'Anna de Vries');

    expect($board->clear)->toBeTrue()
        ->and($board->go)->toBeTrue()
        ->and($board->status())->toBe('GO')
        ->and($board->reasons())->toBe([]);
});

test('clear but unsigned is CLEAR, not GO', function () {
    $board = board(allPassing(), items: allTicked(), studio: 'Sam Visser');

    expect($board->clear)->toBeTrue()
        ->and($board->go)->toBeFalse()
        ->and($board->status())->toBe('CLEAR')
        ->and($board->reasons())->toBe(['Client sign-off: waiting for a signature.']);
});

test('the board is not clear when', function (Board $board, string $reason) {
    expect($board->clear)->toBeFalse()
        ->and($board->go)->toBeFalse()
        ->and($board->status())->toBe('NO-GO')
        ->and($board->reasons())->toContain($reason);
})->with([
    'the checks never ran' => [fn () => board(null, items: allTicked()), 'Automated checks: not run yet.'],
    'a check fails' => [fn () => board([...allPassing(), 'csp' => CheckStatus::Fail], items: allTicked()), 'Automated checks: 1 failing.'],
    'a check warns' => [fn () => board([...allPassing(), 'hsts' => CheckStatus::Warn], items: allTicked()), 'Automated checks: 1 warning.'],
    'a check was skipped' => [fn () => board([...allPassing(), 'hsts' => CheckStatus::Skipped], items: allTicked()), 'Automated checks: 1 skipped.'],
    'the checks ran on an old URL' => [fn () => board(allPassing(), items: allTicked(), current: false), 'Automated checks: they ran against an older site URL; run them again.'],
    'a studio item is open' => [fn () => board(allPassing(), items: [[ChecklistOwner::Studio, false], [ChecklistOwner::Client, true]]), 'Studio checklist: 1 item open.'],
    'client items are open' => [fn () => board(allPassing(), items: [[ChecklistOwner::Client, false], [ChecklistOwner::Client, false]]), 'Client checklist: 2 items open.'],
]);

test('waived checks count as clear', function () {
    $board = board([...allPassing(), 'csp' => CheckStatus::Fail, 'hsts' => CheckStatus::Warn], waived: ['csp', 'hsts'], items: allTicked());

    expect($board->clear)->toBeTrue()
        ->and($board->rows[0]['detail'])->toBe('1 passed, 2 waived.');
});

test('a waiver for one check does not cover another', function () {
    $board = board([...allPassing(), 'csp' => CheckStatus::Fail], waived: ['hsts'], items: allTicked());

    expect($board->clear)->toBeFalse();
});

test('signatures alone never make GO', function () {
    $board = board([...allPassing(), 'csp' => CheckStatus::Fail], items: allTicked(), studio: 'Sam Visser', client: 'Anna de Vries');

    expect($board->go)->toBeFalse()
        ->and($board->status())->toBe('NO-GO');
});

test('sign-off rows explain when they open', function () {
    $board = board(null);

    expect($board->rows[3]['detail'])->toBe('opens once the board is clear.')
        ->and(board(allPassing(), items: allTicked())->rows[3]['detail'])->toBe('waiting for a signature.');
});
