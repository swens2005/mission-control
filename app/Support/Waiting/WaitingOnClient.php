<?php

namespace App\Support\Waiting;

use App\Models\User;
use Closure;

/**
 * Collects everything waiting on a client, across modules.
 *
 * Each module registers a provider once (in a service provider):
 *
 *     app(WaitingOnClient::class)->register(
 *         fn (User $client) => [new WaitingItem(...), ...],
 *     );
 *
 * The client's home page then lists the combined items, soonest due first.
 */
final class WaitingOnClient
{
    /** @var list<Closure(User): iterable<WaitingItem>> */
    private array $providers = [];

    /**
     * @param  Closure(User): iterable<WaitingItem>  $provider
     */
    public function register(Closure $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Items for this client, soonest due date first; items without a due
     * date come last, in the order they were registered.
     *
     * @return list<WaitingItem>
     */
    public function for(User $client): array
    {
        $items = [];

        foreach ($this->providers as $provider) {
            foreach ($provider($client) as $item) {
                $items[] = $item;
            }
        }

        // usort is stable, so equal due dates keep their registration order.
        usort($items, function (WaitingItem $a, WaitingItem $b): int {
            if ($a->dueOn === null || $b->dueOn === null) {
                return ($a->dueOn === null) <=> ($b->dueOn === null);
            }

            return $a->dueOn <=> $b->dueOn;
        });

        return $items;
    }
}
