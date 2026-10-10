<?php

namespace App\Enums;

/**
 * Initial roles. They are a starting point: permissions per role can be changed
 * from the role management page and new roles can be added.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case ZiLeader = 'zi_leader';
    case AreaCoordinator = 'area_coordinator';
    case Implementer = 'implementer';
    case Verifier = 'verifier';
    case ExecutiveViewer = 'executive_viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ZiLeader => 'Ketua Tim ZI',
            self::AreaCoordinator => 'Koordinator Area',
            self::Implementer => 'Pelaksana',
            self::Verifier => 'Verifikator',
            self::ExecutiveViewer => 'Pimpinan (lihat saja)',
        };
    }

    public static function labelFor(string $name): string
    {
        return self::tryFrom($name)?->label() ?? $name;
    }

    /**
     * Default permissions granted by the seeder.
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        $readOnly = [
            Permission::PeriodsView,
            Permission::MasterView,
            Permission::ProgramsView,
            Permission::ActionPlansView,
            Permission::EvidenceView,
        ];

        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::ZiLeader => [
                ...$readOnly,
                Permission::OrganizationsView,
                Permission::UsersView,
                Permission::PeriodsManage,
                Permission::ProgramsCreate,
                Permission::ProgramsUpdate,
                Permission::ProgramsArchive,
                Permission::ActionPlansCreate,
                Permission::ActionPlansUpdate,
                Permission::ActionPlansUpdateProgress,
                Permission::ActionPlansArchive,
                Permission::AssignmentsManage,
                Permission::EvidenceUpload,
                Permission::EvidenceUpdate,
                Permission::EvidenceArchive,
                Permission::ReportsView,
                Permission::ReportsExport,
                Permission::AuditView,
            ],
            self::AreaCoordinator => [
                ...$readOnly,
                Permission::UsersView,
                Permission::ProgramsCreate,
                Permission::ProgramsUpdate,
                Permission::ActionPlansCreate,
                Permission::ActionPlansUpdate,
                Permission::ActionPlansUpdateProgress,
                Permission::AssignmentsManage,
                Permission::EvidenceUpload,
                Permission::EvidenceUpdate,
                Permission::ReportsView,
                Permission::ReportsExport,
            ],
            self::Implementer => [
                ...$readOnly,
                Permission::ActionPlansUpdateProgress,
                Permission::EvidenceUpload,
            ],
            self::Verifier => [
                ...$readOnly,
                Permission::EvidenceVerify,
                Permission::EvidenceRequestRevision,
                Permission::ReportsView,
            ],
            self::ExecutiveViewer => [
                ...$readOnly,
                Permission::ReportsView,
                Permission::ReportsExport,
            ],
        };
    }
}
