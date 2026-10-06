<?php

use App\Enums\ColorRole;
use App\Models\ActivityEntry;
use App\Models\BrandKit;
use App\Models\Color;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Color\Contrast;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($organization)->create(['name' => 'codelaunch.nl']);
    $this->kit = BrandKit::factory()->for($this->project)->create();

    $add = fn (string $name, string $hex, ColorRole $role) => Color::factory()->for($this->kit, 'kit')->hex($hex, $role)->create(['name' => $name]);

    $this->ink = $add('Ink', '#10233a', ColorRole::Text);
    $this->label = $add('Screen label', '#4a7fc4', ColorRole::Text);
    $this->lime = $add('Lime', '#8ac800', ColorRole::Shape);
    $this->cream = $add('Cream', '#f5f2ea', ColorRole::Surface);
    $this->screen = $add('Screen', '#cfe8fa', ColorRole::Surface);
});

test('the matrix grades every foreground on every surface', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('kit.matrix.columns', 2)
            ->where('kit.matrix.columns.0.name', 'Cream')
            ->has('kit.matrix.rows', 3)
            // Ink passes everywhere.
            ->where('kit.matrix.rows.0.cells.0.gradeLabel', 'AAA')
            // The CV blue on the screen gradient: 3.23:1, large text only.
            ->where('kit.matrix.rows.1.cells.1.ratio', '3.23:1')
            ->where('kit.matrix.rows.1.cells.1.gradeLabel', 'AA large only')
            ->where('kit.matrix.rows.1.cells.1.fix.hex', '#3367ab')
            ->where('kit.matrix.rows.1.cells.1.fix.ratio', '4.51:1')
            // Lime is graded as a shape, never as text, and never "fixed".
            ->where('kit.matrix.rows.2.cells.0.gradeLabel', 'Decoration only')
            ->where('kit.matrix.rows.2.cells.0.fix', null)
            ->where('kit.matrix.failing', 2));
});

test('Fix it applies the nearest passing lightness and records it', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.colors.fix', $this->label), ['surface' => $this->screen->id])
        ->assertRedirect(route('admin.palette.show', $this->project));

    $this->label->refresh();

    expect($this->label->hex)->toBe('#3367ab')
        ->and(Contrast::ratio($this->label->hex, $this->screen->hex))->toBeGreaterThanOrEqual(4.5)
        ->and(ActivityEntry::where('event', 'palette.color_fixed')->sole()->description())
        ->toBe('fixed the contrast of Screen label on Screen: #4a7fc4 to #3367ab in codelaunch.nl');
});

test('Fix it refuses shapes, surfaces and surfaces from other kits', function () {
    $other = Color::factory()->hex('#ffffff', ColorRole::Surface)
        ->for(BrandKit::factory()->for(Project::factory()->for($this->project->organization)), 'kit')->create();

    $this->actingAs($this->admin);

    $this->post(route('admin.colors.fix', $this->lime), ['surface' => $this->cream->id])->assertSessionHasErrors('fix');
    $this->post(route('admin.colors.fix', $this->cream), ['surface' => $this->screen->id])->assertSessionHasErrors('fix');
    $this->post(route('admin.colors.fix', $this->label), ['surface' => $other->id])->assertNotFound();
    $this->post(route('admin.colors.fix', $this->label), ['surface' => $this->ink->id])->assertNotFound();

    expect($this->label->refresh()->hex)->toBe('#4a7fc4');
});

test('Fix it is locked once approved, and 404 for other workspaces', function () {
    $this->kit->forceFill(['approved_at' => now()])->save();

    $this->actingAs($this->admin)
        ->post(route('admin.colors.fix', $this->label), ['surface' => $this->screen->id])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.colors.fix', $this->label), ['surface' => $this->screen->id])
        ->assertNotFound();
});
