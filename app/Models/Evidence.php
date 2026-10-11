<?php

namespace App\Models;

use App\Enums\EvidenceCategory;
use App\Enums\VerificationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $period_id
 * @property int|null $indicator_id
 * @property int|null $work_program_id
 * @property int|null $action_plan_id
 * @property string $title
 * @property string|null $description
 * @property EvidenceCategory $category
 * @property VerificationStatus $verification_status
 * @property string|null $notes
 * @property int $uploaded_by
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organization $organization
 * @property-read Period $period
 * @property-read Indicator|null $indicator
 * @property-read WorkProgram|null $workProgram
 * @property-read ActionPlan|null $actionPlan
 * @property-read User $uploader
 * @property-read EvidenceVersion|null $latestVersion
 * @property-read Collection<int, EvidenceVersion> $versions
 * @property-read Collection<int, EvidenceReview> $reviews
 */
#[Table('evidence')]
#[Fillable(['indicator_id', 'work_program_id', 'action_plan_id', 'title', 'description', 'category', 'notes'])]
class Evidence extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => EvidenceCategory::class,
            'verification_status' => VerificationStatus::class,
            'archived_at' => 'datetime',
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
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    /**
     * @return BelongsTo<Indicator, $this>
     */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    /**
     * @return BelongsTo<WorkProgram, $this>
     */
    public function workProgram(): BelongsTo
    {
        return $this->belongsTo(WorkProgram::class);
    }

    /**
     * @return BelongsTo<ActionPlan, $this>
     */
    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(ActionPlan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<EvidenceVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EvidenceVersion::class)->orderByDesc('version_number');
    }

    /**
     * @return HasOne<EvidenceVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(EvidenceVersion::class)->latestOfMany('version_number');
    }

    /**
     * @return HasMany<EvidenceReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(EvidenceReview::class);
    }

    /**
     * @param  Builder<Evidence>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
