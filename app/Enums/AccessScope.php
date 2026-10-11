<?php

namespace App\Enums;

/**
 * Data scope of a user, independent from their functional role.
 */
enum AccessScope: string
{
    use HasLabelOptions;

    case Own = 'own';
    case Descendants = 'descendants';
    case National = 'national';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Organisasi sendiri',
            self::Descendants => 'Organisasi sendiri beserta turunannya',
            self::National => 'Nasional (seluruh organisasi)',
        };
    }
}
