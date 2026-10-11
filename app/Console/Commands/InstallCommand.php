<?php

namespace App\Console\Commands;

use App\Enums\AccessScope;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('zi:install')]
#[Description('Seed reference data and create the first organization and Super Admin account')]
class InstallCommand extends Command
{
    public function handle(): int
    {
        $this->components->info('Menyiapkan role, permission, tahap verifikasi, dan data referensi...');
        $this->callSilent('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $organization = Organization::query()->whereNull('parent_id')->orderBy('id')->first() ?? $this->createOrganization();

        $this->components->info("Organisasi: {$organization->name}");

        if (User::role(Role::SuperAdmin->value)->exists() && ! confirm('Sudah ada akun Super Admin. Buat akun Super Admin tambahan?', default: false)) {
            return self::SUCCESS;
        }
        $this->components->info('Buat akun Super Admin pertama (kata sandi tidak disimpan di mana pun selain sebagai hash).');

        $name = text('Nama lengkap', required: true);
        $email = text('Email', required: true, validate: fn (string $value) => $this->validationError(
            ['email' => $value],
            ['email' => ['email', 'max:255', 'unique:users,email']],
        ));
        $secret = password('Kata sandi', required: true, validate: fn (string $value) => $this->validationError(
            ['password' => $value],
            ['password' => [Password::min(12)->mixedCase()->letters()->numbers()->symbols()]],
        ));
        password('Ulangi kata sandi', required: true, validate: fn (string $value) => $value === $secret ? null : 'Kata sandi tidak sama.');

        DB::transaction(function () use ($name, $email, $secret, $organization) {
            $user = new User(['name' => $name, 'email' => strtolower($email), 'password' => $secret]);
            $user->email_verified_at = now();
            $user->organization_id = $organization->id;
            $user->access_scope = AccessScope::National;
            $user->is_active = true;
            $user->save();
            $user->assignRole(Role::SuperAdmin->value);
        });

        $this->components->info('Akun Super Admin berhasil dibuat. Silakan login melalui aplikasi.');

        return self::SUCCESS;
    }

    private function createOrganization(): Organization
    {
        $this->components->info('Belum ada organisasi. Buat organisasi pertama (organisasi akar).');

        $type = select('Tipe organisasi', collect(OrganizationType::cases())->mapWithKeys(fn (OrganizationType $type) => [$type->value => $type->label()])->all(), default: OrganizationType::KabupatenKota->value);
        $name = text('Nama organisasi', placeholder: 'contoh: BPS Kabupaten ...', required: true);
        $code = text('Kode organisasi (opsional, isi hanya jika kode resmi diketahui)');

        return Organization::create([
            'name' => $name,
            'type' => $type,
            'code' => $code !== '' ? $code : null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     */
    private function validationError(array $data, array $rules): ?string
    {
        $validator = Validator::make($data, $rules);

        return $validator->fails() ? $validator->errors()->first() : null;
    }
}
