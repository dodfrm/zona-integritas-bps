<?php

namespace App\Models;

use App\Enums\ActionPlanStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $action_plan_id
 * @property ActionPlanStatus|null $from_status
 * @property ActionPlanStatus $to_status
 * @property int|null $progress_percent
 * @property string|null $note
 * @property int|null $changed_by
 * @property CarbonImmutable|null $created_at
 * @property-read User|null $changer
 */
class ActionPlanStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ActionPlanStatus::class,
            'to_status' => ActionPlanStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
