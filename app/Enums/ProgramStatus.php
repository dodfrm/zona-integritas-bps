<?php

namespace App\Enums;

enum ProgramStatus: string
{
    use HasLabelOptions;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Direncanakan',
            self::InProgress => 'Berjalan',
            self::Completed => 'Selesai',
            self::OnHold => 'Ditunda',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
