<?php

namespace App\Enums;

/**
 * Administrative verification status of an evidence item (not a substantive ZI assessment).
 */
enum VerificationStatus: string
{
    use HasLabelOptions;

    case Pending = 'pending';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case RevisionRequired = 'revision_required';
    case Rejected = 'rejected';
    case Resubmitted = 'resubmitted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu verifikasi',
            self::InReview => 'Sedang diverifikasi',
            self::Accepted => 'Diterima',
            self::RevisionRequired => 'Perlu revisi',
            self::Rejected => 'Ditolak',
            self::Resubmitted => 'Diajukan kembali',
        };
    }
}
