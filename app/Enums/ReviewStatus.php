<?php

namespace App\Enums;

/**
 * Status of one verification stage instance for one evidence version.
 */
enum ReviewStatus: string
{
    use HasLabelOptions;

    case Waiting = 'waiting';
    case Pending = 'pending';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case RevisionRequired = 'revision_required';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu tahap sebelumnya',
            self::Pending => 'Menunggu verifikasi',
            self::InReview => 'Sedang diverifikasi',
            self::Accepted => 'Diterima',
            self::RevisionRequired => 'Perlu revisi',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Tidak dilanjutkan',
            self::Superseded => 'Digantikan versi baru',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InReview], true);
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [self::Pending->value, self::InReview->value];
    }
}
