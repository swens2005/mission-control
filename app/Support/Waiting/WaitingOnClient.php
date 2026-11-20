<?php

namespace App\Support\Waiting;

use App\Models\Organization;
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
     * What a whole client organization owes the studio: the same list each
     * of its contacts sees, for the flight deck (story 45).
     *
     * Providers only read the client's workspace_id and organization_id
     * (every item belongs to the organization, not to one person), so an
     * unsaved user carrying those two stands in for any contact.
     * WaitingOnClientTest checks that the two views match.
     *
     * @return list<WaitingItem>
     */
    public function forOrganization(Organization $organization): array
    {
        $client = (new User)->forceFill([
            'workspace_id' => $organization->workspace_id,
            'organization_id' => $organization->id,
        ]);

        return $this->for($client);
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
