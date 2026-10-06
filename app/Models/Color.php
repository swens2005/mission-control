<?php

namespace App\Models;

use App\Enums\ColorRole;
use App\Models\Concerns\BelongsToWorkspace;
use App\Support\Color\ColorInput;
use App\Support\Color\OklchColor;
use Carbon\CarbonImmutable;
use Database\Factories\ColorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One brand color, in OKLCH (stored as integers) and hex.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $brand_kit_id
 * @property string $name
 * @property ColorRole $role
 * @property int $position
 * @property int $lightness
 * @property int $chroma
 * @property int $hue
 * @property string $hex
 * @property bool $gamut_adjusted
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BrandKit $kit
 */
class Color extends Model
{
    /** @use HasFactory<ColorFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $color): void {
            $kit = BrandKit::withoutGlobalScopes()->find($color->brand_kit_id);

            $color->workspace_id ??= $kit?->workspace_id;

            if ($kit === null || $kit->workspace_id !== $color->workspace_id) {
                throw new LogicException('A color must belong to a brand kit in its own workspace.');
            }
        });
    }

    /**
     * @return BelongsTo<BrandKit, $this>
     */
    public function kit(): BelongsTo
    {
        return $this->belongsTo(BrandKit::class, 'brand_kit_id');
    }

    public function oklch(): OklchColor
    {
        return new OklchColor($this->lightness, $this->chroma, $this->hue);
    }

    /**
     * Sets hex and OKLCH together from what was typed.
     */
    public function setValue(ColorInput $input): void
    {
        $this->hex = $input->hex;
        $this->lightness = $input->oklch->lightness;
        $this->chroma = $input->oklch->chroma;
        $this->hue = $input->oklch->hue;
        $this->gamut_adjusted = $input->adjusted;
    }

    protected function casts(): array
    {
        return [
            'role' => ColorRole::class,
            'gamut_adjusted' => 'boolean',
        ];
    }
}
