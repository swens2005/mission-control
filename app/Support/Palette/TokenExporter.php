<?php

namespace App\Support\Palette;

use App\Enums\FontChoice;
use App\Models\BrandKit;
use App\Models\Color;
use App\Support\Typography\TypeScale;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The kit as code (story 23): CSS custom properties, a Tailwind 4 @theme
 * block, and W3C Design Tokens JSON. Pure: the same kit always gives the
 * same text.
 */
final class TokenExporter
{
    public const FORMATS = [
        'css' => ['label' => 'CSS variables', 'extension' => 'css', 'mime' => 'text/css'],
        'tailwind' => ['label' => 'Tailwind @theme', 'extension' => 'css', 'mime' => 'text/css'],
        'json' => ['label' => 'Design tokens JSON', 'extension' => 'json', 'mime' => 'application/json'],
    ];

    public static function export(BrandKit $kit, string $format): string
    {
        return match ($format) {
            'css' => self::css($kit),
            'tailwind' => self::tailwind($kit),
            'json' => self::json($kit),
            default => throw new InvalidArgumentException("Unknown export format: {$format}"),
        };
    }

    public static function filename(BrandKit $kit, string $format): string
    {
        $suffix = $format === 'tailwind' ? '-theme' : '-tokens';

        return (Str::slug($kit->project->name) ?: 'brand-kit').$suffix.'.'.self::FORMATS[$format]['extension'];
    }

    public static function css(BrandKit $kit): string
    {
        $lines = ['/* '.$kit->project->name.': brand kit from Mission Control */', ':root {'];

        foreach (self::named($kit->colors) as $slug => $color) {
            $lines[] = "    --{$slug}: {$color->hex}; /* {$color->oklch()->css()} */";
        }

        foreach (self::fonts($kit) as $role => $font) {
            $lines[] = "    --font-{$role}: {$font->stack()};";
        }

        foreach (self::scale($kit) as $step) {
            $lines[] = "    --text-{$step['name']}: {$step['rem']}; /* {$step['px']}px */";
        }

        $lines[] = '}';

        return implode("\n", $lines)."\n";
    }

    public static function tailwind(BrandKit $kit): string
    {
        $lines = ['/* '.$kit->project->name.': paste into your main CSS file, after @import "tailwindcss". */', '@theme {'];

        foreach (self::named($kit->colors) as $slug => $color) {
            $lines[] = "    --color-{$slug}: {$color->hex};";
        }

        foreach (self::fonts($kit) as $role => $font) {
            $lines[] = "    --font-{$role}: {$font->stack()};";
        }

        foreach (self::scale($kit) as $step) {
            $lines[] = "    --text-{$step['name']}: {$step['rem']};";
        }

        $lines[] = '}';

        return implode("\n", $lines)."\n";
    }

    /**
     * W3C Design Tokens format: groups of tokens with $type and $value.
     */
    public static function json(BrandKit $kit): string
    {
        $tokens = ['color' => [], 'font' => [], 'text' => []];

        foreach (self::named($kit->colors) as $slug => $color) {
            $tokens['color'][$slug] = [
                '$type' => 'color',
                '$value' => $color->hex,
                '$description' => $color->name.' · '.$color->role->label().' · '.$color->oklch()->css(),
            ];
        }

        foreach (self::fonts($kit) as $role => $font) {
            $tokens['font'][$role] = [
                '$type' => 'fontFamily',
                '$value' => array_map(fn (string $f) => trim($f, " '"), explode(',', $font->stack())),
            ];
        }

        foreach (self::scale($kit) as $step) {
            $tokens['text'][$step['name']] = [
                '$type' => 'dimension',
                '$value' => ['value' => $step['px'] / 16, 'unit' => 'rem'],
            ];
        }

        return json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * Colors by token name: a slug of the color's name, made unique with
     * -2, -3 when two colors slug the same ("Ink" and "ink!").
     *
     * @param  Collection<int, Color>  $colors
     * @return array<string, Color>
     */
    public static function named(Collection $colors): array
    {
        $named = [];

        foreach ($colors as $color) {
            $base = Str::slug($color->name) ?: 'color';
            $slug = $base;

            for ($n = 2; isset($named[$slug]); $n++) {
                $slug = "{$base}-{$n}";
            }

            $named[$slug] = $color;
        }

        return $named;
    }

    /**
     * @return array{heading: FontChoice, body: FontChoice}
     */
    private static function fonts(BrandKit $kit): array
    {
        return ['heading' => FontChoice::from($kit->heading_font), 'body' => FontChoice::from($kit->body_font)];
    }

    /**
     * @return list<array{name: string, px: int, rem: string}>
     */
    private static function scale(BrandKit $kit): array
    {
        return TypeScale::steps($kit->base_size_px, $kit->scale_ratio, $kit->steps_up, $kit->steps_down);
    }
}
