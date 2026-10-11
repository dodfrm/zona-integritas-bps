<?php

namespace App\Models;

use App\Enums\EvidenceCategory;
use App\Enums\ExecutorLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sequence
 * @property ExecutorLevel $executor_level
 * @property EvidenceCategory|null $evidence_category
 * @property bool $is_active
 */
#[Fillable(['name', 'description', 'sequence', 'executor_level', 'evidence_category', 'is_active'])]
class VerificationStage extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'executor_level' => ExecutorLevel::class,
            'evidence_category' => EvidenceCategory::class,
            'is_active' => 'boolean',
            'sequence' => 'integer',
        ];
    }

    public function appliesTo(Evidence $evidence): bool
    {
        return $this->evidence_category === null || $this->evidence_category === $evidence->category;
    }
}
