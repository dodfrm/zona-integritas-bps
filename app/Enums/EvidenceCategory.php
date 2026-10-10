<?php

namespace App\Enums;

/**
 * Generic document types. These are administrative groupings only,
 * not official evaluation categories.
 */
enum EvidenceCategory: string
{
    use HasLabelOptions;

    case Decree = 'sk';
    case Report = 'laporan';
    case Minutes = 'notulen';
    case Attendance = 'daftar_hadir';
    case Documentation = 'dokumentasi';
    case Letter = 'surat';
    case Data = 'data';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Decree => 'SK / Kebijakan',
            self::Report => 'Laporan',
            self::Minutes => 'Notulen rapat',
            self::Attendance => 'Daftar hadir',
            self::Documentation => 'Foto / dokumentasi',
            self::Letter => 'Surat / nota dinas',
            self::Data => 'Data / rekapitulasi',
            self::Other => 'Lainnya',
        };
    }
}
