<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $guideline_version_id
 * @property string $name
 * @property int $year
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property PeriodStatus $status
 * @property string|null $notes
 * @property CarbonImmutable|null $closed_at
 * @property int|null $closed_by
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organization $organization
 * @property-read GuidelineVersion $guidelineVersion
 */
#[Fillable(['name', 'year', 'start_date', 'end_date', 'notes'])]
class Period extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PeriodStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'year' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<GuidelineVersion, $this>
     */
    public function guidelineVersion(): BelongsTo
    {
        return $this->belongsTo(GuidelineVersion::class);
    }

    /**
     * @return HasMany<WorkProgram, $this>
     */
    public function workPrograms(): HasMany
    {
        return $this->hasMany(WorkProgram::class);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
}
