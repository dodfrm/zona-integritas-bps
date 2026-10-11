<?php

namespace Database\Seeders;

use App\Enums\ExecutorLevel;
use App\Models\VerificationStage;
use Illuminate\Database\Seeder;

/**
 * Only the internal stage is active by default. Province/central stages exist as configuration
 * but stay inactive until that business process is confirmed.
 */
class VerificationStageSeeder extends Seeder
{
    public function run(): void
    {
        if (VerificationStage::query()->exists()) {
            return;
        }

        VerificationStage::create([
            'name' => 'Verifikasi Internal',
            'description' => 'Verifikasi administratif oleh verifikator pada organisasi pemilik bukti dukung.',
            'sequence' => 1,
            'executor_level' => ExecutorLevel::Owner,
            'is_active' => true,
        ]);

        VerificationStage::create([
            'name' => 'Verifikasi BPS Provinsi',
            'description' => 'Belum aktif. Aktifkan bila verifikasi tingkat provinsi sudah ditetapkan.',
            'sequence' => 2,
            'executor_level' => ExecutorLevel::Provinsi,
            'is_active' => false,
        ]);

        VerificationStage::create([
            'name' => 'Verifikasi BPS Pusat',
            'description' => 'Belum aktif. Aktifkan bila verifikasi tingkat pusat sudah ditetapkan.',
            'sequence' => 3,
            'executor_level' => ExecutorLevel::Pusat,
            'is_active' => false,
        ]);
    }
}
