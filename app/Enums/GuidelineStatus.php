<?php

namespace App\Enums;

enum GuidelineStatus: string
{
    use HasLabelOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Berlaku',
            self::Retired => 'Tidak berlaku',
        };
    }
}
