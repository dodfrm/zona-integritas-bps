<?php

namespace App\Models;

use App\Enums\ActionPlanStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $work_program_id
 * @property int $organization_id
 * @property int $period_id
 * @property int|null $indicator_id
 * @property string $title
 * @property string|null $description
 * @property string|null $output_target
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $due_date
 * @property int|null $pic_user_id
 * @property ActionPlanStatus $status
 * @property int|null $progress_percent
 * @property string|null $obstacle_notes
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $archived_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read WorkProgram $workProgram
 * @property-read Organization $organization
 * @property-read Period $period
 * @property-read Indicator|null $indicator
 * @property-read User|null $pic
 * @property-read Collection<int, ActionPlanStatusHistory> $statusHistories
 */
#[Fillable(['indicator_id', 'title', 'description', 'output_target', 'start_date', 'due_date', 'obstacle_notes'])]
class ActionPlan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ActionPlanStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'progress_percent' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WorkProgram, $this>
     */
    public function workProgram(): BelongsTo
    {
        return $this->belongsTo(WorkProgram::class);
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
     * @return BelongsTo<User, $this>
     */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /**
     * @return HasMany<ActionPlanStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ActionPlanStatusHistory::class)->latest('id');
    }

    /**
     * @return HasMany<Evidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * @return HasMany<Assignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(today())
            && in_array($this->status->value, ActionPlanStatus::openValues(), true);
    }

    /**
     * @param  Builder<ActionPlan>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Open (not completed/cancelled) action plans whose due date has passed.
     *
     * @param  Builder<ActionPlan>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->whereIn('status', ActionPlanStatus::openValues());
    }
}
