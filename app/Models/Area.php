<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $guideline_version_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property-read GuidelineVersion $guidelineVersion
 * @property-read Collection<int, Indicator> $indicators
 */
#[Fillable(['code', 'name', 'description', 'sort_order', 'is_active'])]
class Area extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GuidelineVersion, $this>
     */
    public function guidelineVersion(): BelongsTo
    {
        return $this->belongsTo(GuidelineVersion::class);
    }

    /**
     * @return HasMany<Indicator, $this>
     */
    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class)->orderBy('sort_order')->orderBy('code');
    }

    public function label(): string
    {
        return "{$this->code}. {$this->name}";
    }
}
