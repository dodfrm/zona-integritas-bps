<?php

namespace App\Models;

use App\Enums\GuidelineStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $reference
 * @property GuidelineStatus $status
 * @property int|null $copied_from_id
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Area> $areas
 * @property-read GuidelineVersion|null $copiedFrom
 */
#[Fillable(['name', 'description', 'reference'])]
class GuidelineVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GuidelineStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Area, $this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class)->orderBy('sort_order')->orderBy('code');
    }

    /**
     * @return HasManyThrough<Indicator, Area, $this>
     */
    public function indicators(): HasManyThrough
    {
        return $this->hasManyThrough(Indicator::class, Area::class);
    }

    /**
     * @return HasMany<Period, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(Period::class);
    }

    /**
     * @return BelongsTo<GuidelineVersion, $this>
     */
    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(GuidelineVersion::class, 'copied_from_id');
    }

    /**
     * Only draft versions may change; published ones are frozen snapshots.
     */
    public function isEditable(): bool
    {
        return $this->status === GuidelineStatus::Draft;
    }
}
