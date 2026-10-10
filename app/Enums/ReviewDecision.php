<?php

namespace App\Enums;

enum ReviewDecision: string
{
    use HasLabelOptions;

    case Accept = 'accepted';
    case RequestRevision = 'revision_required';
    case Reject = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Accept => 'Terima',
            self::RequestRevision => 'Minta revisi',
            self::Reject => 'Tolak',
        };
    }

    public function reviewStatus(): ReviewStatus
    {
        return ReviewStatus::from($this->value);
    }
}
