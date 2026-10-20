<?php

use App\Enums\LineItemKind;
use App\Models\ActivityEntry;
use App\Models\LineItem;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * @return array<string, string>
 */
function itemInput(array $overrides = []): array
{
    return [
        'name' => 'Seasonal page',
        'description' => 'One page with its own photos and menu.',
        'kind' => 'fixed',
        'unit_price' => '1.250,50',
        'default_quantity' => '2',
        ...$overrides,
    ];
}

test('the studio adds an item, stored in cents and hundredths', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.line-items.store'), itemInput())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.line-items.index'));

    $item = LineItem::sole();

    expect($item->workspace_id)->toBe($this->admin->workspace_id)
        ->and($item->kind)->toBe(LineItemKind::Fixed)
        ->and($item->unit_price_cents)->toBe(125050)
        ->and($item->default_quantity)->toBe(200)
        ->and(ActivityEntry::where('event', 'scope.item_added')->sole()->description())->toContain('Seasonal page (€1,250.50)');

    $this->get(route('admin.line-items.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/scope/library')
            ->has('items', 1)
            ->where('items.0.unitPrice', '€1,250.50')
            ->where('items.0.unitPriceInput', '1250.50')
            ->where('items.0.defaultQuantity', '2')
            ->where('items.0.unit', '×')
            ->has('kinds', 2));
});

test('hourly items take quarter hours', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.line-items.store'), itemInput(['kind' => 'hourly', 'unit_price' => '85', 'default_quantity' => '1,5']))
        ->assertSessionHasNoErrors();

    expect(LineItem::sole()->default_quantity)->toBe(150);
});

test('items are validated', function (array $overrides, string $field) {
    $this->actingAs($this->admin)
        ->post(route('admin.line-items.store'), itemInput($overrides))
        ->assertSessionHasErrors($field);

    expect(LineItem::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'long name' => [['name' => str_repeat('a', 81)], 'name'],
    'unknown kind' => [['kind' => 'monthly'], 'kind'],
    'garbage price' => [['unit_price' => 'cheap'], 'unit_price'],
    'negative price' => [['unit_price' => '-5'], 'unit_price'],
    'three decimals' => [['unit_price' => '12.345.6'], 'unit_price'],
    'price too high' => [['unit_price' => '10000001'], 'unit_price'],
    'zero quantity' => [['default_quantity' => '0'], 'default_quantity'],
    'fixed in halves' => [['default_quantity' => '1.5'], 'default_quantity'],
    'hourly off the quarter' => [['kind' => 'hourly', 'default_quantity' => '1.3'], 'default_quantity'],
    'price as an array' => [['unit_price' => ['1']], 'unit_price'],
]);

test('the studio edits, reorders, archives and restores items', function () {
    $first = LineItem::factory()->for($this->admin->workspace)->create(['name' => 'Design', 'position' => 0]);
    $second = LineItem::factory()->for($this->admin->workspace)->hourly()->create(['position' => 1]);

    $this->actingAs($this->admin)
        ->patch(route('admin.line-items.update', $first), itemInput(['name' => 'Design and build', 'unit_price' => '3000']))
        ->assertRedirect(route('admin.line-items.index'));

    expect($first->refresh()->name)->toBe('Design and build')
        ->and($first->unit_price_cents)->toBe(300000);

    $this->post(route('admin.line-items.move', $second), ['direction' => 'up'])->assertRedirect();

    $this->get(route('admin.line-items.index'))
        ->assertInertia(fn (Assert $page) => $page->where('items.0.id', $second->id)->where('items.1.id', $first->id));

    $this->post(route('admin.line-items.archive', $first))->assertRedirect();

    $this->get(route('admin.line-items.index'))
        ->assertInertia(fn (Assert $page) => $page->has('items', 1)->where('archived.0.id', $first->id));

    $this->delete(route('admin.line-items.restore', $first))->assertRedirect();

    expect($first->refresh()->isArchived())->toBeFalse()
        ->and(ActivityEntry::whereIn('event', ['scope.item_changed', 'scope.item_archived', 'scope.item_restored'])->count())->toBe(3)
        ->and(ActivityEntry::where('visible_to_client', true)->count())->toBe(0);
});

test('other workspaces get a 404, clients a 403', function () {
    $item = LineItem::factory()->for($this->admin->workspace)->create();

    $stranger = User::factory()->admin()->create();
    $this->actingAs($stranger)->patch(route('admin.line-items.update', $item), itemInput())->assertNotFound();
    $this->post(route('admin.line-items.archive', $item))->assertNotFound();
    $this->post(route('admin.line-items.move', $item), ['direction' => 'up'])->assertNotFound();
    $this->get(route('admin.line-items.index'))->assertInertia(fn (Assert $page) => $page->has('items', 0));

    $client = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();
    $this->actingAs($client)->get(route('admin.line-items.index'))->assertForbidden();
    $this->post(route('admin.line-items.store'), itemInput())->assertForbidden();
});
