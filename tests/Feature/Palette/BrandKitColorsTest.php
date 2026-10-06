<?php

use App\Enums\ColorRole;
use App\Enums\ProjectPhase;
use App\Models\ActivityEntry;
use App\Models\BrandKit;
use App\Models\Color;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Palette)->create(['name' => 'Café corner']);
});

function kitFor(Project $project): BrandKit
{
    return BrandKit::factory()->for($project)->create();
}

test('an admin starts a brand kit with codelaunch.nl type defaults', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('admin/palette/show')->where('kit', null)->has('roles', 4));

    $this->post(route('admin.palette.store', $this->project))->assertRedirect(route('admin.palette.show', $this->project));
    $this->post(route('admin.palette.store', $this->project))->assertRedirect();

    $kit = BrandKit::sole();

    expect($kit->workspace_id)->toBe($this->admin->workspace_id)
        ->and($kit->heading_font)->toBe('bricolage')
        ->and($kit->scale_ratio)->toBe(1250)
        ->and(ActivityEntry::where('event', 'palette.kit_created')->count())->toBe(1);
});

test('colors save from hex or oklch and show both', function (string $value, string $hex) {
    $kit = kitFor($this->project);

    $this->actingAs($this->admin)
        ->post(route('admin.colors.store', $kit), ['name' => 'Ink', 'role' => 'text', 'value' => $value])
        ->assertRedirect(route('admin.palette.show', $this->project));

    $color = Color::sole();

    expect($color->hex)->toBe($hex)
        ->and($color->role)->toBe(ColorRole::Text)
        ->and($color->workspace_id)->toBe($this->admin->workspace_id);

    $this->get(route('admin.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kit.colors.0.hex', $hex)
            ->where('kit.colors.0.oklch', $color->oklch()->css())
            ->where('kit.colors.0.roleLabel', 'Text'));
})->with([
    'hex' => ['#10233A', '#10233a'],
    'short hex' => ['#fff', '#ffffff'],
    'oklch' => ['oklch(0.628 0.2577 29.23)', '#ff0000'],
]);

test('an oklch value outside sRGB is adjusted and flagged', function () {
    $kit = kitFor($this->project);

    $this->actingAs($this->admin)->post(route('admin.colors.store', $kit), ['name' => 'Neon', 'role' => 'shape', 'value' => 'oklch(0.7 0.4 145)']);

    $color = Color::sole();

    expect($color->gamut_adjusted)->toBeTrue()
        ->and($color->lightness)->toBe(7_000)
        ->and($color->chroma)->toBeLessThan(4_000);
});

test('bad values, names and roles are refused', function (array $data, string $field) {
    $kit = kitFor($this->project);

    $this->actingAs($this->admin)
        ->post(route('admin.colors.store', $kit), [...['name' => 'Ink', 'role' => 'text', 'value' => '#10233a'], ...$data])
        ->assertRedirect()
        ->assertSessionHasErrors($field);

    expect(Color::count())->toBe(0);
})->with([
    'not a color' => [['value' => 'dark blue'], 'value'],
    'rgb()' => [['value' => 'rgb(1, 2, 3)'], 'value'],
    'empty name' => [['name' => ''], 'name'],
    'unknown role' => [['role' => 'border'], 'role'],
]);

test('colors can be edited, reordered and removed', function () {
    $kit = kitFor($this->project);
    $ink = Color::factory()->for($kit, 'kit')->create(['name' => 'Ink', 'position' => 0]);
    $lime = Color::factory()->for($kit, 'kit')->hex('#8ac800', ColorRole::Shape)->create(['name' => 'Lime', 'position' => 1]);

    $this->actingAs($this->admin);
    $this->patch(route('admin.colors.update', $ink), ['name' => 'Night', 'role' => 'text', 'value' => '#0b1a2c'])->assertRedirect();
    $this->post(route('admin.colors.move', $lime), ['direction' => 'up'])->assertRedirect();

    expect($kit->colors()->pluck('name')->all())->toBe(['Lime', 'Night'])
        ->and($ink->refresh()->hex)->toBe('#0b1a2c');

    $this->delete(route('admin.colors.destroy', $lime))->assertRedirect();

    expect($kit->colors()->pluck('name')->all())->toBe(['Night'])
        ->and(ActivityEntry::pluck('event')->all())->toContain('palette.color_changed', 'palette.color_removed');
});

test('an approved kit is locked', function () {
    $kit = kitFor($this->project);
    $kit->forceFill(['approved_at' => now()])->save();
    $color = Color::factory()->for($kit, 'kit')->create();

    $this->actingAs($this->admin);
    $this->post(route('admin.colors.store', $kit), ['name' => 'Ink', 'role' => 'text', 'value' => '#000'])->assertForbidden();
    $this->patch(route('admin.colors.update', $color), ['name' => 'X', 'role' => 'text', 'value' => '#000'])->assertForbidden();
    $this->delete(route('admin.colors.destroy', $color))->assertForbidden();
});

test('another workspace gets 404 and a client gets 403', function () {
    $kit = kitFor($this->project);
    $color = Color::factory()->for($kit, 'kit')->create();

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.palette.show', $this->project))->assertNotFound();
    $this->post(route('admin.colors.store', $kit), ['name' => 'Ink', 'role' => 'text', 'value' => '#000'])->assertNotFound();
    $this->delete(route('admin.colors.destroy', $color))->assertNotFound();

    $this->actingAs(User::factory()->client($this->organization)->create());
    $this->get(route('admin.palette.show', $this->project))->assertForbidden();
});
