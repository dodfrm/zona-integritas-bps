<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $period_id
 * @property int|null $area_id
 * @property string|null $code
 * @property string $name
 * @property string|null $description
 * @property string|null $objective
 * @property int|null $pic_user_id
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $target_date
 * @property ProgramStatus $status
 * @property string|null $notes
 * @property CarbonImmutable|null $archived_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organization $organization
 * @property-read Period $period
 * @property-read Area|null $area
 * @property-read User|null $pic
 * @property-read Collection<int, Indicator> $indicators
 * @property-read Collection<int, ActionPlan> $actionPlans
 * @property-read int|null $action_plans_count withCount alias
 * @property-read int|null $completed_action_plans_count withCount alias
 * @property-read int|null $overdue_action_plans_count withCount alias
 */
#[Fillable(['area_id', 'code', 'name', 'description', 'objective', 'start_date', 'target_date', 'status', 'notes'])]
class WorkProgram extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
            'start_date' => 'date',
            'target_date' => 'date',
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
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Indicator, $this>
     */
    public function indicators(): BelongsToMany
    {
        return $this->belongsToMany(Indicator::class);
    }

    /**
     * @return HasMany<ActionPlan, $this>
     */
    public function actionPlans(): HasMany
    {
        return $this->hasMany(ActionPlan::class);
    }

    /**
     * @return HasMany<Evidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * @param  Builder<WorkProgram>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
