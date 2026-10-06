<?php

use App\Models\ActivityEntry;
use App\Models\BrandKit;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($organization)->create(['name' => 'Café corner']);
    $this->kit = BrandKit::factory()->for($this->project)->create();
});

function typeSettings(array $overrides = []): array
{
    return [
        'heading_font' => 'system-serif',
        'body_font' => 'figtree',
        'base_size_px' => 18,
        'ratio_preset' => '1333',
        'ratio' => '',
        'steps_up' => 4,
        'steps_down' => 1,
        ...$overrides,
    ];
}

test('the kit shows the default scale and font choices', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('kit.type.headingLabel', 'Bricolage Grotesque')
            ->where('kit.type.ratio', '1.25')
            ->where('kit.type.ratioPreset', '1250')
            ->has('kit.type.scale', 8)
            ->where('kit.type.scale.2.name', 'base')
            ->has('typeOptions.fonts', 6)
            ->where('typeOptions.ratios.4.value', 'custom'));
});

test('an admin sets fonts and a named ratio', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.type.update', $this->kit), typeSettings())
        ->assertRedirect(route('admin.palette.show', $this->project));

    $this->kit->refresh();

    expect($this->kit)
        ->heading_font->toBe('system-serif')
        ->base_size_px->toBe(18)
        ->scale_ratio->toBe(1333)
        ->steps_up->toBe(4)
        ->steps_down->toBe(1)
        ->and(ActivityEntry::where('event', 'palette.type_changed')->sole()->description())
        ->toBe('set the type of Café corner to System serif and Figtree, ratio 1.333');
});

test('a custom ratio is typed as a decimal and stored as thousandths', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.type.update', $this->kit), typeSettings(['ratio_preset' => 'custom', 'ratio' => '1.15']));

    expect($this->kit->refresh()->scale_ratio)->toBe(1150);

    $this->get(route('admin.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->where('kit.type.ratioPreset', 'custom')->where('kit.type.ratio', '1.15'));
});

test('bad type settings are refused', function (array $overrides, string $field) {
    $this->actingAs($this->admin)
        ->put(route('admin.type.update', $this->kit), typeSettings($overrides))
        ->assertRedirect()
        ->assertSessionHasErrorsIn('type', $field);

    expect($this->kit->refresh()->scale_ratio)->toBe(1250);
})->with([
    'unknown font' => [['heading_font' => 'comic-sans'], 'heading_font'],
    'remote font' => [['body_font' => 'https://fonts.example/x.css'], 'body_font'],
    'base too small' => [['base_size_px' => 6], 'base_size_px'],
    'custom ratio missing' => [['ratio_preset' => 'custom', 'ratio' => ''], 'ratio'],
    'custom ratio garbage' => [['ratio_preset' => 'custom', 'ratio' => 'big'], 'ratio'],
    'custom ratio too flat' => [['ratio_preset' => 'custom', 'ratio' => '1.01'], 'ratio'],
    'top size too large' => [['ratio_preset' => '1618', 'steps_up' => 8, 'base_size_px' => 24], 'steps_up'],
    'too many steps' => [['steps_up' => 9], 'steps_up'],
]);

test('type is locked after approval, 404 for other workspaces', function () {
    $this->kit->forceFill(['approved_at' => now()])->save();

    $this->actingAs($this->admin)->put(route('admin.type.update', $this->kit), typeSettings())->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.type.update', $this->kit), typeSettings())->assertNotFound();
});
