<?php

namespace App\Models;

use App\Enums\LineItemKind;
use App\Models\Concerns\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Database\Factories\LineItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One entry in the studio's price list (story 26).
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $description
 * @property LineItemKind $kind
 * @property int $unit_price_cents
 * @property int $default_quantity hundredths: 1.5 hours is 150
 * @property int $position
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class LineItem extends Model
{
    /** @use HasFactory<LineItemFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $guarded = ['*'];

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    protected function casts(): array
    {
        return [
            'kind' => LineItemKind::class,
            'archived_at' => 'datetime',
        ];
    }
}
