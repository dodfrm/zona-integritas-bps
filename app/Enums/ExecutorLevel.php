<?php

namespace App\Enums;

/**
 * Which organization executes a verification stage, relative to the evidence owner.
 */
enum ExecutorLevel: string
{
    use HasLabelOptions;

    case Owner = 'owner';
    case Provinsi = 'provinsi';
    case Pusat = 'pusat';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Organisasi pemilik bukti',
            self::Provinsi => 'BPS Provinsi (induk)',
            self::Pusat => 'BPS Pusat',
        };
    }

    /**
     * Organization type of the ancestor that executes the stage (null = the owner itself).
     */
    public function ancestorType(): ?OrganizationType
    {
        return match ($this) {
            self::Owner => null,
            self::Provinsi => OrganizationType::Provinsi,
            self::Pusat => OrganizationType::Pusat,
        };
    }
}
