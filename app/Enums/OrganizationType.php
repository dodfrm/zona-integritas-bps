<?php

namespace App\Enums;

enum OrganizationType: string
{
    use HasLabelOptions;

    case Pusat = 'pusat';
    case Provinsi = 'provinsi';
    case KabupatenKota = 'kabkota';

    public function label(): string
    {
        return match ($this) {
            self::Pusat => 'BPS Pusat',
            self::Provinsi => 'BPS Provinsi',
            self::KabupatenKota => 'BPS Kabupaten/Kota',
        };
    }
}
