<?php

namespace App\Models;

use App\Enums\ExecutorLevel;
use App\Enums\ReviewStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evidence_id
 * @property int $evidence_version_id
 * @property int|null $verification_stage_id
 * @property string $stage_name
 * @property int $stage_sequence
 * @property ExecutorLevel $executor_level
 * @property int $executor_organization_id
 * @property ReviewStatus $status
 * @property int|null $assigned_verifier_id
 * @property CarbonImmutable|null $started_at
 * @property int|null $started_by
 * @property CarbonImmutable|null $decided_at
 * @property int|null $decided_by
 * @property string|null $decision_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Evidence $evidence
 * @property-read EvidenceVersion $evidenceVersion
 * @property-read Organization $executorOrganization
 * @property-read User|null $assignedVerifier
 * @property-read User|null $decider
 */
class EvidenceReview extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'executor_level' => ExecutorLevel::class,
            'status' => ReviewStatus::class,
            'started_at' => 'datetime',
            'decided_at' => 'datetime',
            'stage_sequence' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    /**
     * @return BelongsTo<EvidenceVersion, $this>
     */
    public function evidenceVersion(): BelongsTo
    {
        return $this->belongsTo(EvidenceVersion::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function executorOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'executor_organization_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_verifier_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
