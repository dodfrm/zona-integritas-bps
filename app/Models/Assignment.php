<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $period_id
 * @property AssignmentType $type
 * @property int|null $work_program_id
 * @property int|null $action_plan_id
 * @property int|null $evidence_id
 * @property int $assignee_id
 * @property int|null $assigned_by
 * @property AssignmentStatus $status
 * @property CarbonImmutable|null $due_date
 * @property string|null $notes
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $assignee
 * @property-read User|null $assigner
 * @property-read WorkProgram|null $workProgram
 * @property-read ActionPlan|null $actionPlan
 * @property-read Evidence|null $evidence
 */
class Assignment extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssignmentType::class,
            'status' => AssignmentStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
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
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
