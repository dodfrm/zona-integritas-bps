<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $area_id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $evidence_guidance
 * @property string|null $weight
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Area $area
 * @property-read Indicator|null $parent
 * @property-read Collection<int, Indicator> $children
 */
#[Fillable(['parent_id', 'code', 'name', 'description', 'evidence_guidance', 'weight', 'sort_order', 'is_active'])]
class Indicator extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'weight' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return BelongsTo<Indicator, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Indicator::class, 'parent_id');
    }

    /**
     * @return HasMany<Indicator, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Indicator::class, 'parent_id');
    }

    public function label(): string
    {
        return "{$this->code} {$this->name}";
    }
}
