<?php

namespace App\Enums;

/**
 * Implementation status. Deliberately separate from evidence verification status:
 * "completed" never means "verified" or "meets ZI substance".
 */
enum ActionPlanStatus: string
{
    use HasLabelOptions;

    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Belum dimulai',
            self::InProgress => 'Sedang berjalan',
            self::Completed => 'Selesai',
            self::OnHold => 'Ditunda',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Statuses that still count as open work (used for overdue detection).
     *
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [self::NotStarted->value, self::InProgress->value, self::OnHold->value];
    }
}
