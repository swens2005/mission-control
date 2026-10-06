<?php

namespace App\Support\Palette;

use App\Enums\ColorRole;
use App\Models\BrandKit;
use App\Models\Color;

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
        ];
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
