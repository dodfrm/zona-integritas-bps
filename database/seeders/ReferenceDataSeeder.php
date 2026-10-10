<?php

namespace Database\Seeders;

use App\Enums\GuidelineStatus;
use App\Models\GuidelineVersion;
use Illuminate\Database\Seeder;

/**
 * Seeds the six commonly used ZI change areas as a DRAFT guideline version without indicators,
 * weights or formulas. An administrator must check names against the official guideline,
 * add indicators, and only then publish the version.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        if (GuidelineVersion::query()->exists()) {
            return;
        }

        $version = new GuidelineVersion([
            'name' => 'Draf Pedoman ZI (perlu verifikasi)',
            'description' => 'Data awal enam area perubahan. Wajib dicocokkan dengan pedoman resmi yang berlaku, lalu lengkapi indikator sebelum diberlakukan.',
        ]);
        $version->status = GuidelineStatus::Draft;
        $version->save();

        $areas = [
            'Manajemen Perubahan',
            'Penataan Tata Laksana',
            'Penataan Sistem Manajemen SDM Aparatur',
            'Penguatan Akuntabilitas Kinerja',
            'Penguatan Pengawasan',
            'Peningkatan Kualitas Pelayanan Publik',
        ];

        foreach ($areas as $index => $name) {
            $version->areas()->create([
                'code' => (string) ($index + 1),
                'name' => $name,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
