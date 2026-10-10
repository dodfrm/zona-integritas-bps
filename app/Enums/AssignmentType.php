<?php

namespace App\Enums;

enum AssignmentType: string
{
    use HasLabelOptions;

    case Pic = 'pic';
    case Implementer = 'implementer';
    case EvidenceCollection = 'evidence_collection';
    case Verification = 'verification';
    case RevisionFollowUp = 'revision_followup';

    public function label(): string
    {
        return match ($this) {
            self::Pic => 'Penanggung jawab',
            self::Implementer => 'Pelaksana',
            self::EvidenceCollection => 'Pengumpulan bukti dukung',
            self::Verification => 'Verifikasi',
            self::RevisionFollowUp => 'Tindak lanjut revisi',
        };
    }
}
