<?php

namespace App\Enums;

/**
 * Action-based permissions (stored by Spatie Laravel Permission).
 * Every check must be combined with the user's organization scope.
 */
enum Permission: string
{
    case OrganizationsView = 'organizations.view';
    case OrganizationsManage = 'organizations.manage';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case PeriodsView = 'periods.view';
    case PeriodsManage = 'periods.manage';
    case MasterView = 'master.view';
    case MasterManage = 'master.manage';
    case ProgramsView = 'programs.view';
    case ProgramsCreate = 'programs.create';
    case ProgramsUpdate = 'programs.update';
    case ProgramsArchive = 'programs.archive';
    case ActionPlansView = 'action_plans.view';
    case ActionPlansCreate = 'action_plans.create';
    case ActionPlansUpdate = 'action_plans.update';
    case ActionPlansUpdateProgress = 'action_plans.update_progress';
    case ActionPlansArchive = 'action_plans.archive';
    case AssignmentsManage = 'assignments.manage';
    case EvidenceView = 'evidence.view';
    case EvidenceUpload = 'evidence.upload';
    case EvidenceUpdate = 'evidence.update';
    case EvidenceArchive = 'evidence.archive';
    case EvidenceVerify = 'evidence.verify';
    case EvidenceRequestRevision = 'evidence.request_revision';
    case ReportsView = 'reports.view';
    case ReportsExport = 'reports.export';
    case AuditView = 'audit.view';
    case VerificationFlowsManage = 'verification_flows.manage';

    public function label(): string
    {
        return match ($this) {
            self::OrganizationsView => 'Melihat organisasi',
            self::OrganizationsManage => 'Mengelola organisasi',
            self::UsersView => 'Melihat pengguna',
            self::UsersManage => 'Mengelola pengguna, role, dan cakupan',
            self::RolesManage => 'Mengelola role dan permission',
            self::PeriodsView => 'Melihat periode ZI',
            self::PeriodsManage => 'Mengelola periode ZI',
            self::MasterView => 'Melihat pedoman, area, dan indikator',
            self::MasterManage => 'Mengelola pedoman, area, dan indikator',
            self::ProgramsView => 'Melihat program kerja',
            self::ProgramsCreate => 'Membuat program kerja',
            self::ProgramsUpdate => 'Mengubah program kerja',
            self::ProgramsArchive => 'Mengarsipkan program kerja',
            self::ActionPlansView => 'Melihat rencana aksi',
            self::ActionPlansCreate => 'Membuat rencana aksi',
            self::ActionPlansUpdate => 'Mengubah rencana aksi',
            self::ActionPlansUpdateProgress => 'Memperbarui progres rencana aksi yang ditugaskan',
            self::ActionPlansArchive => 'Mengarsipkan rencana aksi',
            self::AssignmentsManage => 'Menetapkan penanggung jawab dan penugasan',
            self::EvidenceView => 'Melihat dan mengunduh bukti dukung',
            self::EvidenceUpload => 'Mengunggah bukti dukung',
            self::EvidenceUpdate => 'Mengubah bukti dukung milik orang lain',
            self::EvidenceArchive => 'Mengarsipkan bukti dukung',
            self::EvidenceVerify => 'Memverifikasi bukti dukung',
            self::EvidenceRequestRevision => 'Meminta revisi bukti dukung',
            self::ReportsView => 'Melihat laporan',
            self::ReportsExport => 'Mengekspor data',
            self::AuditView => 'Melihat audit trail',
            self::VerificationFlowsManage => 'Mengelola konfigurasi alur verifikasi',
        };
    }

    public function group(): string
    {
        return match (explode('.', $this->value)[0]) {
            'organizations', 'users', 'roles' => 'Administrasi',
            'periods', 'master' => 'Periode & master data',
            'programs' => 'Program kerja',
            'action_plans', 'assignments' => 'Rencana aksi & penugasan',
            'evidence', 'verification_flows' => 'Bukti dukung & verifikasi',
            default => 'Laporan & audit',
        };
    }
}
