<?php

namespace App\Support\Palette;

use App\Enums\ColorRole;
use App\Models\Color;
use App\Support\Color\Contrast;
use App\Support\Color\ContrastFixer;
use App\Support\Color\Oklch;
use Illuminate\Support\Collection;

/**
 * Every foreground color (text, accent, shape) on every surface, graded
 * (story 21). Failing text cells carry a "Fix it" suggestion.
 */
final class ContrastMatrix
{
    /**
     * @param  Collection<int, Color>  $colors
     * @return array{columns: list<array{id: int, name: string, hex: string}>, rows: list<array<string, mixed>>, failing: int}
     */
    public static function build(Collection $colors): array
    {
        $surfaces = $colors->filter(fn (Color $c) => $c->role === ColorRole::Surface)->values();
        $foregrounds = $colors->filter(fn (Color $c) => $c->role !== ColorRole::Surface)->values();

        $failing = 0;
        $rows = [];

        foreach ($foregrounds as $color) {
            $cells = [];

            foreach ($surfaces as $surface) {
                $cell = self::cell($color, $surface);
                $failing += ContrastGrade::from($cell['grade'])->fixable() ? 1 : 0;
                $cells[] = $cell;
            }

            $rows[] = [
                'id' => $color->id,
                'name' => $color->name,
                'hex' => $color->hex,
                'role' => $color->role->value,
                'roleLabel' => $color->role->label(),
                'cells' => $cells,
            ];
        }

        return [
            'columns' => array_values($surfaces->map(fn (Color $s) => ['id' => $s->id, 'name' => $s->name, 'hex' => $s->hex])->all()),
            'rows' => $rows,
            'failing' => $failing,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function cell(Color $color, Color $surface): array
    {
        $ratio = Contrast::ratio($color->hex, $surface->hex);
        $grade = $color->role === ColorRole::Shape
            ? ContrastGrade::forShape($ratio)
            : ContrastGrade::forText($ratio);

        return [
            'surfaceId' => $surface->id,
            'ratio' => self::format($ratio),
            'grade' => $grade->value,
            'gradeLabel' => $grade->label(),
            'tone' => $grade->tone(),
            'fix' => $grade->fixable() ? self::suggestion($color, $surface) : null,
        ];
    }

    /**
     * The AA fix for a text or accent color on this surface.
     *
     * @return array{hex: string, oklch: string, ratio: string}|null
     */
    public static function suggestion(Color $color, Color $surface): ?array
    {
        $fixed = ContrastFixer::fix($color->oklch(), $surface->hex, Contrast::AA_TEXT);

        if ($fixed === null) {
            return null;
        }

        $hex = Oklch::toHex($fixed);

        return [
            'hex' => $hex,
            'oklch' => $fixed->css(),
            'ratio' => self::format(Contrast::ratio($hex, $surface->hex)),
        ];
    }

    /**
     * "4.82:1", rounded down, so a ratio shown as passing really passes.
     */
    public static function format(float $ratio): string
    {
        return number_format(floor($ratio * 100) / 100, 2).':1';
    }
}
