<?php

namespace App\Enums;

enum PeriodStatus: string
{
    use HasLabelOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Active => 'Aktif',
            self::Closed => 'Ditutup',
            self::Archived => 'Diarsipkan',
        };
    }

    /**
     * Operational data of the period can only change while it is draft or active.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Active], true);
    }
}
