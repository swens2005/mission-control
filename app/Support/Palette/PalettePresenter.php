<?php

namespace App\Support\Palette;

use App\Enums\ColorRole;
use App\Enums\FontChoice;
use App\Models\BrandKit;
use App\Models\Color;
use App\Support\Typography\TypeScale;

/**
 * Palette Lab's kit as plain arrays for Inertia.
 */
final class PalettePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function kit(BrandKit $kit): array
    {
        return [
            'id' => $kit->id,
            'locked' => $kit->isApproved(),
            'colors' => $kit->colors->map(fn (Color $color) => self::color($color))->values()->all(),
            'matrix' => ContrastMatrix::build($kit->colors),
            'type' => self::type($kit),
        ];
    }

    /**
     * The three exports, for the studio (story 23).
     *
     * @return list<array{format: string, label: string, filename: string, content: string}>
     */
    public static function exports(BrandKit $kit): array
    {
        $exports = [];

        foreach (TokenExporter::FORMATS as $format => $meta) {
            $exports[] = [
                'format' => $format,
                'label' => $meta['label'],
                'filename' => TokenExporter::filename($kit, $format),
                'content' => TokenExporter::export($kit, $format),
            ];
        }

        return $exports;
    }

    /**
     * @return array<string, mixed>
     */
    public static function color(Color $color): array
    {
        return [
            'id' => $color->id,
            'name' => $color->name,
            'role' => $color->role->value,
            'roleLabel' => $color->role->label(),
            'hex' => $color->hex,
            'oklch' => $color->oklch()->css(),
            'gamutAdjusted' => $color->gamut_adjusted,
        ];
    }

    /**
     * Fonts and the modular scale (story 22).
     *
     * @return array<string, mixed>
     */
    public static function type(BrandKit $kit): array
    {
        $heading = FontChoice::from($kit->heading_font);
        $body = FontChoice::from($kit->body_font);

        return [
            'headingFont' => $heading->value,
            'headingLabel' => $heading->label(),
            'headingStack' => $heading->stack(),
            'bodyFont' => $body->value,
            'bodyLabel' => $body->label(),
            'bodyStack' => $body->stack(),
            'baseSizePx' => $kit->base_size_px,
            'ratio' => TypeScale::formatRatio($kit->scale_ratio),
            'ratioPreset' => array_key_exists($kit->scale_ratio, TypeScale::RATIOS) ? (string) $kit->scale_ratio : 'custom',
            'stepsUp' => $kit->steps_up,
            'stepsDown' => $kit->steps_down,
            'scale' => TypeScale::steps($kit->base_size_px, $kit->scale_ratio, $kit->steps_up, $kit->steps_down),
        ];
    }

    /**
     * Choices for the type form.
     *
     * @return array{fonts: list<array{value: string, label: string}>, ratios: list<array{value: string, label: string}>}
     */
    public static function typeOptions(): array
    {
        $ratios = [];

        foreach (TypeScale::RATIOS as $value => $label) {
            $ratios[] = ['value' => (string) $value, 'label' => $label];
        }

        return [
            'fonts' => array_map(fn (FontChoice $font) => ['value' => $font->value, 'label' => $font->label()], FontChoice::cases()),
            'ratios' => [...$ratios, ['value' => 'custom', 'label' => 'Custom']],
        ];
    }

    /**
     * @return list<array{value: string, label: string, hint: string}>
     */
    public static function roles(): array
    {
        return array_map(fn (ColorRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
            'hint' => $role->hint(),
        ], ColorRole::cases());
    }
}
