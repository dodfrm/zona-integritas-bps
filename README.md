# ZI-TERPADU

Sistem Informasi Pengelolaan Program Kerja, Rencana Aksi, dan Bukti Dukung **Zona Integritas (ZI)** untuk BPS Kabupaten/Kota, dengan fondasi multi-organisasi agar dapat diperluas ke BPS Provinsi dan BPS Pusat.

Aplikasi membantu transparansi proses, keterlacakan aktivitas, dan konsistensi data administrasi ZI: area & indikator, periode, program kerja, rencana aksi, penugasan, bukti dukung, verifikasi/revisi, dashboard, laporan, dan audit trail.

> Aplikasi **tidak** menjamin tercapainya predikat ZI. Status "Diterima" pada bukti dukung adalah keputusan verifikasi administratif, bukan penilaian substantif. Laporan di aplikasi bukan laporan resmi evaluasi ZI.

---

## Daftar isi

1. [Stack teknologi](#stack-teknologi)
2. [Status implementasi](#status-implementasi)
3. [Persyaratan environment](#persyaratan-environment)
4. [Instalasi](#instalasi)
5. [Akun awal, role, organisasi, dan cakupan](#akun-awal-role-organisasi-dan-cakupan)
6. [Modul](#modul)
7. [Alur verifikasi yang diimplementasikan](#alur-verifikasi-yang-diimplementasikan)
8. [Penyimpanan berkas bukti dukung](#penyimpanan-berkas-bukti-dukung)
9. [Data historis, versioning, dan pengarsipan](#data-historis-versioning-dan-pengarsipan)
10. [Arsitektur & basis data](#arsitektur--basis-data)
11. [Keamanan](#keamanan)
12. [Batasan MVP](#batasan-mvp)
13. [Rencana pengembangan provinsi & pusat](#rencana-pengembangan-provinsi--pusat)
14. [Plugin & skill agent (Ponytail, UI/UX Pro Max)](#plugin--skill-agent)
15. [Pemeriksaan teknis](#pemeriksaan-teknis)
16. [Checklist pengujian manual](#checklist-pengujian-manual)

---

## Stack teknologi

| Lapisan    | Teknologi                                                                                                    |
| ---------- | ------------------------------------------------------------------------------------------------------------ |
| Backend    | Laravel 13, PHP (lihat persyaratan), Eloquent, Fortify (autentikasi), Spatie Laravel Permission 8            |
| Basis data | MySQL dengan InnoDB (migration & seeder Laravel)                                                             |
| Frontend   | Inertia.js 3, React 19, TypeScript, Vite 8 (vite-plus), Tailwind CSS 4, komponen shadcn/Radix yang sudah ada |
| Routing TS | Laravel Wayfinder (helper route TypeScript digenerate otomatis saat build/dev)                               |
| Font       | Atkinson Hyperlegible (self-hosted dari `@fontsource`, tanpa CDN)                                            |

Tidak memakai Filament, Laravel Shield, atau frontend terpisah.

## Status implementasi

**Selesai (berfungsi end-to-end, diuji secara teknis — lihat [Pemeriksaan teknis](#pemeriksaan-teknis)):**

- Autentikasi (login/logout, lupa kata sandi, 2FA, passkey bawaan starter kit), profil dasar. Registrasi publik **dimatikan**; akun dibuat administrator.
- Manajemen pengguna: buat/ubah, aktif/nonaktif (akun nonaktif tidak bisa login dan sesinya diputus), organisasi, role, cakupan akses, organisasi tambahan.
- Role & permission (Spatie) dengan 6 role awal + pembuatan role baru dan pengaturan permission per role.
- Organisasi hierarkis (pusat/provinsi/kabkota) dengan validasi anti-siklus.
- Periode ZI per organisasi: draf → aktif → ditutup → diarsipkan (satu periode aktif per organisasi).
- Pedoman, area, indikator & subindikator dengan **versioning snapshot** (draf → berlaku/terkunci → tidak berlaku; salin ke versi baru).
- Program kerja (area, indikator, PIC, jadwal, status, arsip).
- Rencana aksi (PIC, tenggat, status pelaksanaan, progres yang dilaporkan, hambatan, riwayat status, indikator keterlambatan, arsip).
- Penugasan (PIC, pelaksana, pengumpulan bukti, verifikator, tindak lanjut revisi) + halaman **Tugas Saya**.
- Repositori bukti dukung privat dengan versi berkas, checksum SHA-256, unduhan terotorisasi.
- Verifikasi bukti (mulai, terima, minta revisi, tolak) dengan riwayat per versi, pemisahan tugas, dan alur bertahap yang dapat dikonfigurasi.
- Dashboard berbasis permission & cakupan (data aktual, definisi setiap persentase ditampilkan).
- Laporan (7 jenis) + ekspor CSV dengan filter & cakupan yang sama.
- Audit trail (append-only) terintegrasi di proses bisnis.
- Notifikasi internal (database) + pengingat tenggat terjadwal.

**Sebagian:**

- Verifikasi tingkat provinsi/pusat: struktur data, konfigurasi tahap, eksekusi, dan otorisasinya sudah ada dan teruji, tetapi tahapnya **nonaktif secara default** dan belum ada proses bisnis resmi yang dikonfirmasi.
- Dashboard: agregasi per **satu periode** (satu organisasi). Agregasi lintas organisasi (provinsi/pusat) belum dibuat.
- Bobot indikator hanya disimpan sebagai konfigurasi; **tidak ada rumus penilaian** yang aktif.

**Belum diimplementasikan:**

- Notifikasi email/WhatsApp (hanya notifikasi internal; email bawaan Laravel memakai `MAIL_MAILER` dari `.env`).
- Ekspor selain CSV (PDF/XLSX).
- Impor massal (indikator/pengguna) dan penghapusan permanen data operasional (sengaja; gunakan arsip).
- Automated test suite baru (sesuai instruksi; pengujian dilakukan manual).

## Persyaratan environment

- **PHP ≥ 8.4.1** — `composer.lock` saat ini mengunci paket Symfony 8 yang mensyaratkan PHP 8.4.1+ (meski `composer.json` menulis `^8.3`). CI memakai PHP 8.5. Ekstensi: `pdo_mysql`, `mbstring`, `fileinfo` (wajib untuk deteksi tipe berkas), `intl`, `openssl`, `zip`.
- Composer 2.
- Node.js 22 + npm.
- **MySQL 8.x (InnoDB)**. Migration juga sudah diverifikasi di MariaDB 10.11; MySQL 8 sendiri dipakai di CI tetapi belum dijalankan langsung oleh pengembang.
- Batas unggah PHP (`upload_max_filesize`, `post_max_size`) ≥ `ZI_EVIDENCE_MAX_KB` (default 20 MB).

## Instalasi

### 1. Dependensi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Konfigurasi `.env`

Bagian penting (contoh lengkap ada di `.env.example`):

```dotenv
APP_NAME=ZI-TERPADU
APP_URL=https://zi.contoh.go.id   # URL sebenarnya (dipakai passkey & tautan)
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta          # dipakai untuk menentukan "terlambat"
APP_DEBUG=false                    # wajib false di production

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zi_terpadu
DB_USERNAME=zi_terpadu
DB_PASSWORD=isi-kata-sandi-db

ZI_EVIDENCE_DISK=local             # disk privat (storage/app/private)
ZI_EVIDENCE_MAX_KB=20480
ZI_SEPARATION_OF_DUTIES=true       # pengunggah tidak boleh memverifikasi bukti yang sama
ZI_REMINDER_DAYS_BEFORE_DUE=3
```

Jangan menyimpan kredensial nyata di repository.

### 3. Membuat database MySQL

```sql
CREATE DATABASE zi_terpadu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'zi_terpadu'@'localhost' IDENTIFIED BY 'ganti-dengan-kata-sandi-kuat';
GRANT ALL PRIVILEGES ON zi_terpadu.* TO 'zi_terpadu'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Migration, seeder, dan akun awal

```bash
php artisan migrate
php artisan zi:install
```

`zi:install` (interaktif) akan:

1. menjalankan seeder aman (`DatabaseSeeder`): role & permission, tahap verifikasi, dan **draf** pedoman berisi enam area awal tanpa indikator/bobot;
2. membuat organisasi akar bila belum ada (tipe, nama, kode opsional — isi kode hanya bila kode resmi diketahui);
3. membuat akun **Super Admin** bercakupan nasional. Kata sandi diketik saat itu juga (minimal 12 karakter, huruf besar/kecil, angka, simbol) dan hanya disimpan sebagai hash.

Seeder dapat dijalankan ulang dengan aman: `php artisan db:seed` (idempoten; tidak menimpa perubahan permission role selain Super Admin dan tidak membuat akun). Tidak ada akun dengan kata sandi tetap.

> Jangan menjalankan `migrate:fresh` pada database yang sudah berisi data.

### 5. Menjalankan aplikasi

Pengembangan (server, queue, log, Vite sekaligus):

```bash
composer dev
```

atau terpisah: `php artisan serve` dan `npm run dev`.

Build frontend untuk production:

```bash
npm run build
```

Scheduler (pengingat tenggat harian 07.00) — tambahkan cron di server:

```cron
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

Notifikasi dikirim langsung (tidak memerlukan queue worker), tetapi `QUEUE_CONNECTION=database` tetap dipakai komponen Laravel lain.

### 6. Production checklist singkat

`APP_ENV=production`, `APP_DEBUG=false`, HTTPS, `php artisan config:cache route:cache view:cache`, cron scheduler, backup database **dan** folder `storage/app/private/evidence`.

## Akun awal, role, organisasi, dan cakupan

### Role awal (titik awal, dapat diubah)

| Role               | Ringkasan permission default                                                     |
| ------------------ | -------------------------------------------------------------------------------- |
| `super_admin`      | Semua permission (selalu disinkronkan)                                           |
| `zi_leader`        | Kelola periode, program, rencana aksi, penugasan, bukti; laporan & ekspor; audit |
| `area_coordinator` | Buat/ubah program & rencana aksi, penugasan, unggah/ubah bukti, laporan & ekspor |
| `implementer`      | Lihat data, perbarui progres rencana aksi yang ditugaskan, unggah bukti          |
| `verifier`         | Lihat data, verifikasi & minta revisi bukti, lihat laporan                       |
| `executive_viewer` | Lihat data, laporan & ekspor                                                     |

Ubah di menu **Administrasi → Role & Permission** (permission `roles.manage`). Daftar permission ada di `app/Enums/Permission.php`; default per role di `app/Enums/Role.php`.

### Organisasi

Menu **Administrasi → Organisasi**. Setiap organisasi memiliki tipe dan induk opsional. Organisasi akar (tanpa induk) hanya dapat dibuat pengguna bercakupan nasional. Sistem menolak induk yang merupakan dirinya sendiri atau turunannya. Organisasi dinonaktifkan, bukan dihapus.

### Cakupan organisasi pengguna

Role menentukan **tindakan**, cakupan menentukan **data organisasi** yang boleh diakses. Keduanya selalu dicek di backend.

| Cakupan                               | Akses data                                                                   |
| ------------------------------------- | ---------------------------------------------------------------------------- |
| Organisasi sendiri                    | hanya organisasi pengguna                                                    |
| Organisasi sendiri beserta turunannya | mis. BPS Provinsi beserta kabupaten/kota di bawahnya                         |
| Nasional                              | seluruh organisasi (hanya dapat diberikan oleh pengguna bercakupan nasional) |
| Organisasi tambahan                   | organisasi tertentu yang ditugaskan eksplisit, opsional termasuk turunannya  |

Atur di **Administrasi → Pengguna → Ubah**. Pengguna tidak dapat memberi cakupan/organisasi di luar cakupannya sendiri, dan hanya Super Admin yang dapat memberi role Super Admin. Pengelolaan **master data pedoman** (yang dipakai semua organisasi) mensyaratkan permission `master.manage` **dan** cakupan nasional.

## Modul

| Menu                                    | Fungsi utama                                                                                                                                                                                   |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Dashboard                               | KPI periode, progres per area (dengan definisi), status rencana aksi & bukti, rencana aksi terlambat, bukti perlu revisi, antrean verifikasi, pekerjaan saya. Bagian tampil sesuai permission. |
| Tugas Saya                              | Rencana aksi yang dipegang/ditugaskan, penugasan aktif, bukti yang perlu diperbaiki, tautan antrean verifikasi.                                                                                |
| Program Kerja                           | Daftar per periode (filter area/status/PIC/arsip), formulir, detail (indikator, progres, rencana aksi, bukti, penugasan), arsip.                                                               |
| Rencana Aksi                            | Daftar (filter program/area/status/PIC/terlambat), detail, pembaruan status/progres/hambatan dengan riwayat, penugasan, arsip.                                                                 |
| Bukti Dukung                            | Unggah, metadata, versi baru, unduh, riwayat versi & verifikasi, arsip, filter.                                                                                                                |
| Verifikasi                              | Antrean sesuai tahap & cakupan, riwayat keputusan.                                                                                                                                             |
| Laporan                                 | Rekap program, rencana aksi, progres per area, bukti per status, bukti perlu revisi, pekerjaan terlambat, riwayat verifikasi + ekspor CSV.                                                     |
| Audit Trail                             | Catatan tindakan penting (baca-saja).                                                                                                                                                          |
| Periode ZI                              | Kelola periode dan statusnya.                                                                                                                                                                  |
| Pedoman & Indikator                     | Versi pedoman, area, indikator/subindikator, urutan, aktif/nonaktif, petunjuk bukti, bobot (konfigurasi saja).                                                                                 |
| Alur Verifikasi                         | Konfigurasi tahap verifikasi.                                                                                                                                                                  |
| Organisasi, Pengguna, Role & Permission | Administrasi.                                                                                                                                                                                  |
| Notifikasi (ikon lonceng)               | Notifikasi internal.                                                                                                                                                                           |

**Rumus yang ditampilkan:**

- _Penyelesaian rencana aksi_ = jumlah rencana aksi berstatus **Selesai** ÷ jumlah rencana aksi yang tidak dibatalkan/diarsipkan.
- _Bukti diterima_ = jumlah bukti berstatus **Diterima** ÷ seluruh bukti aktif.
- _Progres (%) rencana aksi_ adalah angka yang **dilaporkan pelaksana**, bukan hasil perhitungan.
- Bila penyebut 0, aplikasi menampilkan "Belum ada data", bukan 0%.

## Alur verifikasi yang diimplementasikan

1. Saat bukti diunggah (versi 1), sistem membuat satu baris _tahap verifikasi_ untuk setiap tahap **aktif** yang berlaku (urutan, jenis bukti). Tahap pertama berstatus _Menunggu verifikasi_, sisanya _Menunggu tahap sebelumnya_. Nama/urutan/tingkat tahap disalin (snapshot), sehingga perubahan konfigurasi tidak menulis ulang riwayat.
2. Tahap dijalankan oleh **organisasi pelaksana**: tingkat _pemilik_ = organisasi pemilik bukti; _provinsi_/_pusat_ = induk bertipe tersebut. Tahap tanpa organisasi pelaksana dilewati. Bila tidak ada tahap yang berlaku, sistem tetap menjalankan _Verifikasi internal_ oleh organisasi pemilik.
3. Verifikator yang berhak: aktif, memiliki permission `evidence.verify` (terima/tolak) atau `evidence.request_revision` (minta revisi), **anggota organisasi pelaksana**, cakupannya mencakup organisasi pemilik bukti, bukan pengunggah bukti/versi tersebut (pemisahan tugas, dapat dimatikan via `ZI_SEPARATION_OF_DUTIES`), dan—bila verifikator ditetapkan—hanya verifikator itu.
4. Keputusan:
    - **Terima** → tahap berikutnya terbuka; bila tidak ada lagi, bukti _Diterima_.
    - **Minta revisi** (catatan wajib) → tahap selanjutnya dibatalkan, bukti _Perlu revisi_, penugasan _tindak lanjut revisi_ dibuat untuk pengunggah, pengunggah diberi notifikasi.
    - **Tolak** (catatan wajib) → bukti _Ditolak_.
5. **Versi baru** (dari status selain _Sedang diverifikasi_) menyimpan berkas baru tanpa menghapus yang lama, menandai tahap yang masih terbuka sebagai _Digantikan versi baru_, dan **memulai ulang seluruh tahap dari awal**. Status bukti menjadi _Diajukan kembali_ bila sebelumnya revisi/ditolak.
6. Keputusan tingkat lebih tinggi tidak menghapus keputusan tingkat sebelumnya; setiap keputusan tercatat (siapa, kapan, catatan, versi) dan masuk audit trail. Keputusan dikunci dengan `SELECT … FOR UPDATE` untuk mencegah keputusan ganda.

Default: hanya **Verifikasi Internal** aktif. Tahap _Verifikasi BPS Provinsi_ dan _Verifikasi BPS Pusat_ tersedia tetapi nonaktif.

## Penyimpanan berkas bukti dukung

- Disimpan di disk privat `local` (`storage/app/private/evidence/{organisasi}/{bukti}/{uuid}.{ekstensi}`); fitur `serve` disk lokal dimatikan sehingga tidak ada URL publik.
- Unduhan hanya melalui `GET /evidence/{bukti}/versions/{versi}/download`, yang memeriksa login, permission, cakupan organisasi, dan keterkaitan versi–bukti (scoped binding), dengan rate limit.
- Validasi server: ukuran maksimum dan tipe berdasarkan **isi berkas** (`mimes`): pdf, doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp, jpg, jpeg, png. Nama file dari klien hanya disimpan sebagai metadata; nama penyimpanan memakai UUID dan ekstensi hasil deteksi server.
- Versi lama tidak pernah dihapus/ditimpa; setiap versi menyimpan checksum SHA-256, ukuran, MIME, pengunggah, dan catatan perubahan.
- Backup folder `storage/app/private/evidence` bersama database.

## Data historis, versioning, dan pengarsipan

- **Pedoman**: versi _berlaku_ terkunci. Periode hanya dapat memakai versi berlaku. Program, rencana aksi, dan bukti merujuk area/indikator milik versi pedoman periodenya, sehingga perubahan pedoman untuk periode baru (via salinan versi) tidak memengaruhi periode lama. Indikator yang sudah dipakai tidak dapat dihapus (foreign key `RESTRICT`).
- **Periode**: ditutup → semua data periode menjadi baca-saja tanpa mengubah data/keputusan. Pedoman periode hanya dapat diganti saat draf dan belum ada program.
- **Program/rencana aksi/bukti**: diarsipkan (`archived_at`), bukan dihapus. Organisasi & periode program, serta program rencana aksi, tidak dapat dipindah setelah dibuat.
- **Verifikasi**: satu baris per tahap per versi; tidak pernah ditimpa.
- **Organisasi**: dinonaktifkan, tidak dihapus; perubahan struktur tidak memindahkan kepemilikan data lama.
- **Pengguna**: dinonaktifkan, tidak dihapus (penghapusan akun mandiri dihapus dari starter kit).
- **Audit trail**: model menolak update/delete; tidak ada route untuk mengubahnya.

Soft delete tidak dipakai; strategi per entitas dijelaskan di atas.

## Arsitektur & basis data

```
app/
  Enums/           status, tipe, permission & role (label bahasa Indonesia)
  Models/          Eloquent models + relasi
  Policies/        otorisasi permission + cakupan (trait ScopedPolicy)
  Services/        proses bisnis: OrganizationScope, VerificationService, EvidenceService,
                   AssignmentService, WorkProgramService, ActionPlanService, PeriodService,
                   GuidelineService, UserManagementService, DashboardService, ReportService,
                   AuditLogger, Notifier
  Http/Controllers + Http/Requests  tipis; validasi di Form Request
  Support/         Present (bentuk data ke frontend), PeriodContext, Options
  Console/Commands zi:install, zi:send-deadline-reminders
resources/js/
  pages/           halaman Inertia per modul
  components/zi/   komponen bersama: PageHeader, DataTable, FilterBar, Pagination, FormField,
                   StatusBadge, ConfirmDialog, EmptyState, ProgressBar, StatCard, AssignmentPanel
```

Tabel utama: `organizations`, `users` (+`organization_id`, `access_scope`, `is_active`), `user_organization_access`, tabel Spatie, `guideline_versions`, `areas`, `indicators`, `periods`, `work_programs`, `indicator_work_program`, `action_plans`, `action_plan_status_histories`, `assignments`, `evidence`, `evidence_versions`, `verification_stages`, `evidence_reviews`, `audit_logs`, `notifications`.

Keputusan desain:

- `organization_id` dan `period_id` disalin ke `action_plans` dan `evidence` untuk query/otorisasi yang efisien. Nilainya selalu diambil dari induk di server (tidak dari request) dan tidak dapat berubah setelah dibuat, sehingga konsisten.
- Verifikasi memakai tabel konfigurasi tahap + instans tahap per versi (bukan kolom tetap `status_kabupaten/provinsi/pusat`).
- Penugasan memakai foreign key eksplisit (`work_program_id` / `action_plan_id` / `evidence_id`), bukan relasi polimorfik tanpa constraint.
- JSON hanya dipakai untuk `audit_logs.properties` (ringkasan perubahan yang fleksibel).
- Fitur _Teams_ bawaan starter kit (workspace swadaftar + undangan) dan registrasi publik **dihapus** atas persetujuan pemilik repository karena bertentangan dengan model organisasi yang dikelola admin. Migration lama tetap ada; tabel `teams`, `team_members`, `team_invitations`, dan kolom `users.current_team_id` dibiarkan (tidak dipakai) agar tidak ada operasi destruktif.

## Keamanan

- Semua route aplikasi di balik `auth` + `verified`; setiap aksi memeriksa **permission dan cakupan organisasi** di backend (policy, gate, query scoping). Data di luar cakupan → 403 (juga untuk akses via URL/ID langsung, unduhan, dan ekspor).
- Validasi server via Form Request; mass assignment dibatasi (`#[Fillable]`), kolom sistem (status, organisasi, arsip) hanya diubah oleh service.
- Rate limit: login, 2FA, passkey (bawaan), unduhan (60/menit), unggahan (30/menit), ekspor (10/menit).
- Ekspor CSV dinetralkan dari _formula injection_.
- Audit log menyaring kunci sensitif (password, token, secret, path berkas).
- Halaman error ramah tanpa stack trace (403/404, dan 500/503 di luar local).
- CSRF bawaan Laravel/Inertia aktif.

## Batasan MVP

- Satu periode aktif per organisasi; dashboard/laporan per periode.
- Nama area awal berasal dari instruksi proyek dan disimpan sebagai **draf yang wajib dicocokkan** dengan pedoman resmi sebelum diberlakukan. Indikator, kode resmi, bobot, dan rumus penilaian **tidak** diisi oleh sistem.
- Tidak ada perhitungan nilai/predikat ZI.
- Konfigurasi tahap verifikasi bersifat global (berlaku untuk semua organisasi).
- Pengguna dapat mengubah email sendiri di profil; email baru perlu verifikasi melalui email (memerlukan konfigurasi mail).
- Pengingat tenggat dikirim setiap hari selama rencana aksi belum selesai (tanpa deduplikasi).

## Rencana pengembangan provinsi & pusat

1. Buat organisasi provinsi/pusat dan pindahkan organisasi kabupaten/kota ke bawahnya (hanya mengubah `parent_id`; data lama tetap milik organisasinya).
2. Beri pengguna provinsi cakupan _Organisasi sendiri beserta turunannya_; pengguna pusat cakupan _Nasional_ hanya bila kewenangannya ditetapkan.
3. Aktifkan tahap _Verifikasi BPS Provinsi_/_Pusat_ di menu Alur Verifikasi setelah proses bisnisnya resmi (dapat dibatasi per jenis bukti).
4. Pengembangan berikutnya yang disarankan: dashboard agregat lintas organisasi (per tahun, dibatasi cakupan), konfigurasi alur per provinsi, laporan agregat hierarkis, notifikasi email, ekspor XLSX/PDF, deduplikasi pengingat.

Struktur data (hierarki organisasi, cakupan pengguna, instans tahap verifikasi) sudah mendukung langkah di atas tanpa perubahan skema mendasar.

## Plugin & skill agent

Bagian ini mencatat penggunaan tool pada lingkungan pengembangan (Claude Code), bukan dependensi aplikasi.

- **UI/UX Pro Max** (`ui-ux-pro-max@ui-ux-pro-max-skill`, v2.13.0): terpasang lewat `claude plugin marketplace add nextlevelbuilder/ui-ux-pro-max-skill` dan `claude plugin install ui-ux-pro-max@ui-ux-pro-max-skill`. Design system `design-system/zi-terpadu/MASTER.md` (navy + biru kontras tinggi, Atkinson Hyperlegible, densitas dashboard) dipakai sebagai sumber token; pencarian pedoman UX (tabel, validasi form, Inertia/shadcn) dipakai saat membangun komponen. MASTER.md hanya diformat ulang oleh formatter repo, isinya tidak diubah.
- **Ponytail** (`ponytail@ponytail`, v5.1.0): terpasang lewat `claude plugin marketplace add DietrichGebert/ponytail` dan `claude plugin install ponytail@ponytail`. Dipakai sebagai pedoman gaya kerja "perubahan terkecil yang lengkap" (reuse komponen yang ada, tanpa abstraksi/dependensi berlebih). Anjuran Ponytail untuk menambah test tidak diikuti karena instruksi proyek melarang automated test suite baru.

## Pemeriksaan teknis

Dijalankan saat pengembangan (lihat juga laporan akhir):

| Pemeriksaan                                                                                                                                                                  | Hasil                                      |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| `php artisan migrate` (+ `db:seed`) di MariaDB 10.11 (InnoDB) dan SQLite                                                                                                     | Berhasil                                   |
| `vendor/bin/phpstan analyse` (Larastan, level 7)                                                                                                                             | 0 error                                    |
| `vendor/bin/pint --test`                                                                                                                                                     | Lolos                                      |
| `npm run types:check` (tsc)                                                                                                                                                  | Lolos                                      |
| `npm run check` (lint + format vite-plus)                                                                                                                                    | Lolos                                      |
| `npm run build`                                                                                                                                                              | Berhasil                                   |
| Test bawaan starter kit yang disesuaikan (Pest, 37 test)                                                                                                                     | Lolos                                      |
| Skrip smoke-test sementara (alur verifikasi bertingkat, cakupan, arsip, laporan) dan uji browser Playwright (semua halaman, formulir, unggah/unduh, akses lintas organisasi) | Lolos — skrip tidak disimpan di repository |

Catatan lingkungan: pemeriksaan di atas memakai PHP 8.3 dengan dependensi yang di-resolve ulang untuk PHP 8.3 (lock repository mensyaratkan PHP 8.4.1+), sehingga perlu diulang di server target.

## Checklist pengujian manual

Lihat [`docs/PENGUJIAN-MANUAL.md`](docs/PENGUJIAN-MANUAL.md).
