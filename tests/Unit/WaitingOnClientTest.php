<?php

use App\Models\User;
use App\Support\Waiting\WaitingItem;
use App\Support\Waiting\WaitingOnClient;
use Carbon\CarbonImmutable;

function waitingItem(string $title, ?string $due = null): WaitingItem
{
    return new WaitingItem($title, 'Module', 1, 'Project', '/x', $due === null ? null : CarbonImmutable::parse($due));
}

test('is empty with no providers', function () {
    expect((new WaitingOnClient)->for(new User))->toBe([]);
});

test('combines providers and sorts by due date, undated items last', function () {
    $waiting = new WaitingOnClient;
    $waiting->register(fn () => [waitingItem('No date A'), waitingItem('Late', '2026-12-01')]);
    $waiting->register(fn () => [waitingItem('Early', '2026-10-10'), waitingItem('No date B')]);

    $titles = array_map(fn (WaitingItem $item) => $item->title, $waiting->for(new User));

    expect($titles)->toBe(['Early', 'Late', 'No date A', 'No date B']);
});

test('passes the client to every provider', function () {
    $client = new User(['name' => 'Anna']);
    $waiting = new WaitingOnClient;
    $waiting->register(fn (User $user) => [waitingItem("For {$user->name}")]);

    expect($waiting->for($client)[0]->title)->toBe('For Anna');
});

test('items serialize with an ISO due date', function () {
    expect(waitingItem('X', '2026-11-05')->toArray()['dueOn'])->toBe('2026-11-05')
        ->and(waitingItem('Y')->toArray()['dueOn'])->toBeNull();
});
