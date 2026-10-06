<?php

use App\Enums\ColorRole;
use App\Models\BrandKit;
use App\Models\Color;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Support\Palette\TokenExporter;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($organization)->create(['name' => 'Café corner']);
    // A small kit: two steps up, one down, a major third on 16 px.
    $this->kit = BrandKit::factory()->for($this->project)->create(['steps_up' => 2, 'steps_down' => 1]);
    Color::factory()->for($this->kit, 'kit')->hex('#10233a')->create(['name' => 'Ink', 'position' => 0]);
    Color::factory()->for($this->kit, 'kit')->hex('#8ac800', ColorRole::Shape)->create(['name' => 'Lime', 'position' => 1]);
    $this->kit->load(['colors', 'project']);
});

test('CSS custom properties', function () {
    expect(TokenExporter::css($this->kit))->toBe(<<<'CSS'
        /* Café corner: brand kit from Mission Control */
        :root {
            --ink: #10233a; /* oklch(25.3% 0.051 254.3) */
            --lime: #8ac800; /* oklch(76.14% 0.201 128.6) */
            --font-heading: 'Bricolage Grotesque', ui-sans-serif, sans-serif;
            --font-body: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            --text-sm: 0.8125rem; /* 13px */
            --text-base: 1rem; /* 16px */
            --text-lg: 1.25rem; /* 20px */
            --text-xl: 1.5625rem; /* 25px */
        }

        CSS);
});

test('a Tailwind 4 @theme block', function () {
    expect(TokenExporter::tailwind($this->kit))->toBe(<<<'CSS'
        /* Café corner: paste into your main CSS file, after @import "tailwindcss". */
        @theme {
            --color-ink: #10233a;
            --color-lime: #8ac800;
            --font-heading: 'Bricolage Grotesque', ui-sans-serif, sans-serif;
            --font-body: 'Figtree', ui-sans-serif, system-ui, sans-serif;
            --text-sm: 0.8125rem;
            --text-base: 1rem;
            --text-lg: 1.25rem;
            --text-xl: 1.5625rem;
        }

        CSS);
});

test('W3C design tokens JSON', function () {
    $tokens = json_decode(TokenExporter::json($this->kit), true);

    expect($tokens['color']['ink'])->toBe([
        '$type' => 'color',
        '$value' => '#10233a',
        '$description' => 'Ink · Text · oklch(25.3% 0.051 254.3)',
    ])
        ->and($tokens['color']['lime']['$description'])->toContain('Shapes only')
        ->and($tokens['font']['heading'])->toBe(['$type' => 'fontFamily', '$value' => ['Bricolage Grotesque', 'ui-sans-serif', 'sans-serif']])
        ->and($tokens['text']['xl'])->toBe(['$type' => 'dimension', '$value' => ['value' => 1.5625, 'unit' => 'rem']])
        ->and(array_keys($tokens['text']))->toBe(['sm', 'base', 'lg', 'xl']);
});

test('token names are slugs, unique within the kit', function () {
    $colors = collect([
        Color::factory()->make(['name' => 'Ink']),
        Color::factory()->make(['name' => 'ink!']),
        Color::factory()->make(['name' => 'Ink']),
        Color::factory()->make(['name' => 'Bakkerij Bruin']),
        Color::factory()->make(['name' => '???']),
    ]);

    expect(array_keys(TokenExporter::named($colors)))->toBe(['ink', 'ink-2', 'ink-3', 'bakkerij-bruin', 'color']);
});

test('the page offers all three exports', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.palette.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('exports', 3)
            ->where('exports.0.label', 'CSS variables')
            ->where('exports.1.filename', 'cafe-corner-theme.css')
            ->where('exports.2.filename', 'cafe-corner-tokens.json'));
});

test('exports download as files', function (string $format, string $type, string $filename) {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.tokens.export', [$this->kit, $format]))
        ->assertOk()
        ->assertHeader('Content-Type', $type)
        ->assertHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');

    expect($response->getContent())->toBe(TokenExporter::export($this->kit, $format));
})->with([
    ['css', 'text/css; charset=UTF-8', 'cafe-corner-tokens.css'],
    ['tailwind', 'text/css; charset=UTF-8', 'cafe-corner-theme.css'],
    ['json', 'application/json; charset=UTF-8', 'cafe-corner-tokens.json'],
]);

test('unknown formats and other workspaces get 404', function () {
    $this->actingAs($this->admin)->get(route('admin.tokens.export', [$this->kit, 'scss']))->assertNotFound();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.tokens.export', [$this->kit, 'css']))->assertNotFound();
});
