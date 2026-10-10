# MASTER PROMPT IMPLEMENTASI ZI-TERPADU

## Sistem Informasi Pengelolaan Program Kerja, Rencana Aksi, dan Bukti Dukung Zona Integritas BPS Kabupaten/Kota

## 1. PERAN DAN TANGGUNG JAWAB

Bertindaklah sebagai **Senior Full-Stack Engineer, Software Architect, Database Designer, dan UI/UX Engineer** yang bertanggung jawab mengimplementasikan aplikasi web produksi bernama **ZI-TERPADU**.

Kamu harus bekerja langsung pada repository yang tersedia di lingkungan kerja. Jangan hanya memberikan rancangan, analisis, contoh kode, atau rekomendasi. Lakukan perubahan file yang diperlukan, implementasikan fitur secara nyata, integrasikan frontend dan backend, jalankan pemeriksaan teknis yang relevan, serta perbaiki kesalahan yang ditemukan.

Prioritas utama:

1. Menghasilkan aplikasi yang berfungsi, bukan sekadar mockup atau prototipe visual.
2. Menjaga kompatibilitas dengan codebase yang sudah ada.
3. Menghasilkan arsitektur yang mudah dikembangkan, aman, dan mudah dipelihara.
4. Menyediakan pengalaman pengguna yang profesional dan sesuai konteks instansi pemerintahan.
5. Membangun fondasi multi-organisasi yang memungkinkan ekspansi dari BPS Kabupaten/Kota ke BPS Provinsi dan BPS Pusat.
6. Menggunakan plugin dan skill yang ditentukan pada bagian berikut apabila tersedia dan kompatibel.
7. Mendokumentasikan hasil implementasi secara jujur, termasuk fitur yang belum selesai.

Jangan berhenti setelah membuat rencana implementasi. Setelah analisis awal, lanjutkan ke implementasi tanpa menunggu persetujuan untuk setiap langkah teknis yang wajar dan tidak destruktif.

## 2. WAJIB MENGGUNAKAN PLUGIN DAN SKILL

Sebelum mulai mengimplementasikan aplikasi, siapkan dan verifikasi plugin serta skill berikut pada coding-agent environment yang digunakan.

### 2.1 Ponytail

Marketplace:
`DietrichGebert/ponytail`

Perintah instalasi yang harus dicoba sesuai dukungan CLI agent:

```text
/plugin marketplace add DietrichGebert/ponytail
/plugin install ponytail@ponytail
```

Gunakan Ponytail sebagai alat bantu pengembangan apabila plugin menyediakan kemampuan yang relevan untuk mengelola pekerjaan, alur implementasi, atau koordinasi tugas. Pelajari dokumentasi dan petunjuk penggunaannya terlebih dahulu, kemudian manfaatkan fitur yang benar-benar tersedia.

### 2.2 UI/UX Pro Max

Marketplace:
`nextlevelbuilder/ui-ux-pro-max-skill`

Perintah instalasi yang harus dicoba sesuai dukungan CLI agent:

```text
/plugin marketplace add nextlevelbuilder/ui-ux-pro-max-skill
/plugin install ui-ux-pro-max@ui-ux-pro-max-skill
```

Gunakan UI/UX Pro Max sebagai skill wajib dalam proses perancangan dan implementasi antarmuka. Baca instruksi skill dan ikuti workflow yang disediakan, termasuk pemilihan design system, tipografi, warna, layout, komponen, responsivitas, serta prinsip usability apabila didukung oleh skill tersebut.

Skill ini harus digunakan sebelum merancang atau mengubah tampilan utama aplikasi, bukan hanya dicantumkan sebagai dependensi.

### 2.3 Aturan penggunaan plugin dan skill

- Periksa terlebih dahulu apakah CLI coding agent mendukung perintah marketplace dan plugin di atas.
- Jalankan perintah instalasi yang sesuai dengan environment aktual.
- Verifikasi bahwa plugin dan skill berhasil terpasang dan benar-benar dapat digunakan.
- Baca dokumentasi atau instruksi resmi dari plugin dan skill sebelum menggunakannya.
- Ikuti petunjuk penggunaan yang disediakan, bukan mengasumsikan fungsi yang tidak terdokumentasi.
- Jika salah satu plugin atau skill gagal dipasang, diagnosis penyebabnya dan coba langkah pemulihan yang aman.
- Jangan mengarang bahwa plugin atau skill sudah aktif apabila belum terverifikasi.
- Jika environment tidak mendukung instalasi tersebut, jelaskan hambatannya dan gunakan kemampuan alternatif yang tersedia tanpa mengklaim telah menggunakan plugin yang tidak aktif.
- Jangan menghapus, mengganti, atau merusak konfigurasi agent yang sudah ada hanya demi memasang plugin.
- Jangan mengorbankan integritas repository atau keamanan lingkungan kerja demi memenuhi proses instalasi.

Setelah menyiapkan plugin dan skill, lanjutkan langsung ke implementasi aplikasi.

## 3. DESKRIPSI APLIKASI

Nama aplikasi: **ZI-TERPADU**.

ZI-TERPADU merupakan aplikasi web untuk membantu pengelolaan pelaksanaan program Zona Integritas (ZI), mulai dari pengelolaan area dan indikator, program kerja, rencana aksi, penugasan pelaksana, pengumpulan bukti dukung, verifikasi, tindak lanjut revisi, pemantauan progres, hingga pelaporan.

Aplikasi ditujukan untuk mendukung pengelolaan proses ZI di lingkungan BPS.

Masalah yang ingin diselesaikan:

- Informasi program kerja dan rencana aksi berpotensi tersebar di banyak dokumen atau spreadsheet.
- Penanggung jawab dan progres pelaksanaan sulit dipantau secara terpusat.
- Bukti dukung sulit ditelusuri berdasarkan area, indikator, program, periode, dan penanggung jawab.
- Proses verifikasi serta revisi membutuhkan pencatatan yang konsisten.
- Pimpinan membutuhkan ringkasan progres dan hambatan berdasarkan data aktual.
- Perubahan pedoman atau indikator dapat menyulitkan pelacakan data historis jika tidak dikelola dengan baik.
- Pemantauan lintas organisasi akan diperlukan apabila penggunaan aplikasi diperluas ke tingkat provinsi dan pusat.

Aplikasi harus membantu transparansi proses, keterlacakan aktivitas, konsistensi data, dan efisiensi administrasi. Jangan mengklaim bahwa aplikasi secara otomatis menjamin tercapainya predikat ZI atau hasil evaluasi substantif.

## 4. STRATEGI IMPLEMENTASI DAN RUANG LINGKUP

Implementasikan aplikasi secara bertahap.

### Tahap pertama: MVP Kabupaten/Kota

Prioritas implementasi:

- Autentikasi dan manajemen pengguna.
- Manajemen role dan permission.
- Manajemen organisasi.
- Manajemen periode ZI.
- Manajemen area dan indikator.
- Manajemen program kerja.
- Manajemen rencana aksi.
- Penugasan pelaksana.
- Repositori bukti dukung.
- Verifikasi dan revisi bukti dukung.
- Dashboard berdasarkan role.
- Laporan dan ekspor data.
- Audit trail.
- Dokumentasi instalasi dan checklist pengujian manual.

Fitur operasional harus dapat digunakan oleh satu instansi BPS Kabupaten/Kota tanpa mengharuskan adanya BPS Provinsi atau BPS Pusat yang aktif di aplikasi.

### Kesiapan ekspansi

Sejak awal, rancang arsitektur agar dapat mendukung:

- Banyak BPS Kabupaten/Kota.
- BPS Provinsi yang membawahi beberapa kabupaten/kota.
- BPS Pusat yang dapat memantau organisasi lintas provinsi.
- Pembatasan akses berdasarkan hierarki organisasi.
- Verifikasi lintas tingkat jika proses bisnis tersebut nantinya diberlakukan.
- Dashboard agregat lintas organisasi.
- Penambahan tahap verifikasi tanpa perubahan arsitektur yang mendasar.

Fitur lintas tingkat tidak harus semuanya aktif pada MVP. Namun, hindari desain yang memaksa perubahan besar pada struktur basis data, identitas pengguna, dan mekanisme otorisasi saat ekspansi dilakukan.

## 5. STACK TEKNOLOGI WAJIB

Gunakan teknologi berikut:

### Backend

- Laravel, dengan versi yang kompatibel dengan repository dan dependensi yang tersedia.
- PHP sesuai persyaratan versi Laravel yang dipakai.
- MySQL dengan InnoDB.
- Eloquent ORM.
- Laravel migrations dan seeders.
- Laravel validation dan authorization.
- Laravel Filesystem untuk pengelolaan berkas.
- Spatie Laravel Permission untuk role dan permission.

### Frontend

- Inertia.js.
- React.
- TypeScript.
- Vite.
- Tailwind CSS.
- Komponen UI yang konsisten dan sesuai dengan design system hasil penggunaan UI/UX Pro Max.

### Ketentuan

- Gunakan versi dependensi yang kompatibel, stabil, dan sesuai dengan codebase.
- Pertahankan dependensi yang sudah terpasang dan masih layak digunakan.
- Jangan meng-upgrade semua dependensi secara massal tanpa alasan teknis.
- Jangan menggunakan Filament.
- Jangan menggunakan Laravel Shield.
- Jangan mengganti stack menjadi framework lain.
- Jangan membuat frontend terpisah yang tidak diperlukan apabila arsitektur Inertia.js sudah mencukupi.
- Hindari penambahan library yang berlebihan.
- Gunakan TypeScript dengan tipe yang jelas dan hindari penggunaan `any` tanpa alasan yang kuat.
- Jangan membuat data dummy permanen sebagai pengganti integrasi backend yang sebenarnya.

## 6. INSPEKSI REPOSITORY SEBELUM IMPLEMENTASI

Sebelum mengubah file:

1. Periksa struktur direktori dan status repository.
2. Periksa versi PHP, Laravel, Node.js, package manager, dan dependensi yang tersedia.
3. Periksa `composer.json`, `composer.lock`, `package.json`, lockfile frontend, konfigurasi Vite, Inertia.js, dan Tailwind CSS.
4. Periksa konfigurasi autentikasi dan sistem otorisasi yang sudah tersedia.
5. Periksa model, migration, controller, service, middleware, route, komponen React, dan halaman yang sudah dibuat.
6. Identifikasi fitur yang sudah berfungsi agar tidak dibuat ulang secara tidak perlu.
7. Periksa konfigurasi `.env.example`, tanpa membocorkan isi rahasia `.env`.
8. Periksa apakah aplikasi sudah memiliki data atau koneksi basis data yang digunakan.
9. Baca instruksi repository seperti `AGENTS.md`, `CLAUDE.md`, atau dokumentasi sejenis jika tersedia.
10. Periksa kompatibilitas dengan plugin dan skill yang diwajibkan.

Jika repository sudah berisi proyek Laravel, lanjutkan dari implementasi yang ada.

Jika repository belum berisi proyek, buat fondasi Laravel dengan React, TypeScript, dan Inertia.js menggunakan starter kit resmi atau metode yang kompatibel dengan versi Laravel yang dipilih.

Jangan menghapus implementasi lama hanya karena struktur atau gaya kodenya berbeda dari preferensi pribadi. Refactor jika memang dibutuhkan, dengan perubahan yang terukur.

Jangan melakukan `migrate:fresh`, menghapus basis data, menghapus migration lama, atau melakukan operasi destruktif lain terhadap data yang ada tanpa kebutuhan yang jelas dan otorisasi eksplisit.

Setelah inspeksi, susun urutan kerja secara internal dan langsung lanjutkan implementasi.

## 7. DESAIN ARSITEKTUR APLIKASI

Gunakan arsitektur modular yang mudah dipelihara.

Pisahkan tanggung jawab secara jelas:

- Routes.
- Controllers.
- Form Requests atau validasi request yang setara.
- Policies dan mekanisme authorization.
- Services untuk proses bisnis yang kompleks.
- Eloquent Models dan Relationships.
- Resources atau presenter jika diperlukan.
- React pages.
- React components.
- Hooks dan utilities.
- TypeScript types.
- Database migrations dan seeders.

Hindari:

- Controller dengan seluruh logika bisnis aplikasi.
- Pengulangan aturan otorisasi di banyak tempat tanpa pola yang konsisten.
- Query basis data yang tidak diperlukan.
- Komponen frontend yang terlalu besar dan menggabungkan terlalu banyak tanggung jawab.
- Logika bisnis penting yang hanya diterapkan pada frontend.
- Ketergantungan antarmodul yang tidak perlu.

Gunakan transaksi basis data pada operasi multi-langkah yang harus konsisten.

Terapkan eager loading, pagination, indeks, dan optimasi query yang sesuai.

Gunakan mekanisme konfigurasi dan dependency injection Laravel sesuai kebutuhan.

## 8. MULTI-ORGANISASI DAN HIERARKI BPS

Bagian ini merupakan persyaratan arsitektur utama.

### 8.1 Entitas organisasi

Buat model organisasi yang fleksibel, misalnya `Organization`, untuk merepresentasikan:

- BPS Kabupaten/Kota.
- BPS Provinsi.
- BPS Pusat.

Atribut minimal dapat meliputi:

- ID.
- Kode organisasi jika tersedia.
- Nama organisasi.
- Tipe organisasi.
- ID organisasi induk yang nullable.
- Status aktif.
- Timestamp.

Sesuaikan nama atribut dengan konvensi codebase.

Gunakan relasi organisasi induk dan anak agar struktur organisasi tidak ditentukan hanya melalui kolom tetap seperti `kabupaten_id` dan `provinsi_id` yang berpotensi membatasi ekspansi.

Validasi agar organisasi tidak menjadi induk dari dirinya sendiri dan tidak membentuk siklus hierarki.

Organisasi pusat dapat menjadi root organisasi. Struktur aktual harus dapat disesuaikan dengan tata kelola yang dikonfirmasi.

Jangan mengarang kode resmi organisasi.

### 8.2 Cakupan data

Data operasional harus dapat ditelusuri ke organisasi pemiliknya.

Tentukan secara konsisten bagaimana organisasi dihubungkan dengan:

- Pengguna.
- Periode ZI.
- Program kerja.
- Rencana aksi.
- Penugasan.
- Bukti dukung.
- Verifikasi.
- Tindak lanjut.
- Laporan.
- Audit trail.

Hindari duplikasi `organization_id` pada semua tabel tanpa mempertimbangkan integritas relasi. Namun, jika kolom organisasi diperlukan untuk query dan otorisasi, pastikan konsistensinya dijaga melalui desain relasi dan validasi yang tepat.

### 8.3 Pemisahan role dan organisasi

Role menunjukkan jenis kewenangan fungsional. Organisasi menentukan cakupan data yang boleh diakses.

Contoh:

- Seorang `verifier` di BPS Kabupaten A tidak otomatis dapat memverifikasi BPS Kabupaten B.
- Seorang `verifier` di BPS Provinsi X dapat diberi cakupan untuk kabupaten/kota di bawah provinsi tersebut.
- Seorang pengguna BPS Pusat dapat diberi cakupan lintas provinsi apabila kewenangannya mengizinkan.

Jangan menjadikan nama role sebagai satu-satunya dasar akses data.

### 8.4 Otorisasi lintas organisasi

Semua pemeriksaan harus dilakukan di backend.

Cegah akses tidak sah ketika pengguna:

- Mengganti ID data pada URL.
- Mengubah parameter request.
- Memanggil endpoint API secara langsung.
- Mengunduh berkas milik organisasi lain.
- Memverifikasi data di luar cakupan kewenangannya.
- Mengakses laporan agregat yang tidak diizinkan.

Gunakan policies, gates, middleware, query scoping, atau mekanisme lain yang sesuai dengan Laravel. Terapkan pola otorisasi yang konsisten dan dapat digunakan kembali.

Untuk fitur ekspansi, dukung cakupan seperti:

- Organisasi sendiri.
- Organisasi sendiri beserta turunannya.
- Organisasi tertentu yang secara eksplisit ditugaskan.
- Cakupan nasional apabila memang diizinkan.

Jangan menganggap seluruh pengguna tingkat provinsi atau pusat otomatis memiliki akses penuh.

## 9. AUTENTIKASI, PENGGUNA, ROLE, DAN PERMISSION

Gunakan mekanisme autentikasi Laravel yang kompatibel dengan proyek.

Jika belum tersedia, implementasikan autentikasi dengan Inertia.js dan React.

Sediakan:

- Login.
- Logout.
- Pengelolaan profil dasar.
- Manajemen pengguna oleh pihak yang berwenang.
- Aktivasi dan penonaktifan pengguna.
- Penetapan organisasi pengguna.
- Penetapan role dan permission.
- Pembatasan akses halaman dan tindakan.

Gunakan Spatie Laravel Permission.

Role awal yang disarankan:

- `super_admin`
- `zi_leader`
- `area_coordinator`
- `implementer`
- `verifier`
- `executive_viewer`

Role tersebut merupakan titik awal, bukan aturan final yang tidak dapat diubah.

Definisikan permission berdasarkan tindakan, misalnya:

- Melihat data.
- Membuat data.
- Mengubah data.
- Mengarsipkan data.
- Mengelola master data.
- Menetapkan penanggung jawab.
- Mengunggah bukti dukung.
- Memverifikasi bukti.
- Meminta revisi.
- Melihat laporan.
- Mengekspor data.
- Mengelola pengguna.
- Mengelola konfigurasi alur verifikasi.

Permission dan cakupan organisasi harus sama-sama diperiksa.

Jangan menggunakan pemeriksaan role yang tersebar sebagai pengganti permission yang jelas apabila tindakan membutuhkan kontrol lebih rinci.

Sediakan seeder role dan permission agar instalasi awal dapat dilakukan secara konsisten.

Jangan membuat akun administrator dengan kata sandi tetap yang tidak aman. Dokumentasikan cara membuat akun awal secara aman.

## 10. PERIODE ZONA INTEGRITAS

Sediakan modul manajemen periode ZI.

Fitur:

- Membuat periode.
- Mengatur nama atau tahun periode.
- Menentukan tanggal mulai dan selesai jika dibutuhkan.
- Mengaktifkan atau menutup periode.
- Melihat periode sebelumnya.
- Mengarsipkan periode sesuai kebijakan.

Periode harus terhubung dengan organisasi yang menjalankannya.

Sistem perlu mendukung beberapa organisasi yang memiliki periode kerja masing-masing, dengan tetap memungkinkan laporan lintas organisasi apabila fitur tersebut diaktifkan.

Data historis tidak boleh hilang atau berubah secara tidak sengaja ketika periode ditutup.

Perubahan periode tidak boleh otomatis mengubah data dan keputusan periode terdahulu.

## 11. AREA DAN INDIKATOR ZONA INTEGRITAS

Sediakan modul master area dan indikator.

Enam area awal yang dapat dijadikan data dasar:

1. Manajemen Perubahan.
2. Penataan Tata Laksana.
3. Penataan Sistem Manajemen SDM Aparatur.
4. Penguatan Akuntabilitas Kinerja.
5. Penguatan Pengawasan.
6. Peningkatan Kualitas Pelayanan Publik.

Nama area, indikator, subindikator, kriteria, dan bobot harus diverifikasi terhadap pedoman resmi yang berlaku sebelum digunakan sebagai data resmi.

Jangan mengarang nomor peraturan, nama indikator resmi, nilai bobot, rumus penilaian, atau ketentuan evaluasi.

Fitur:

- Menambahkan dan mengubah master area.
- Mengelola indikator dan subindikator.
- Mengatur urutan tampilan.
- Mengaktifkan dan menonaktifkan indikator.
- Mengelola deskripsi dan metadata.
- Mengaitkan indikator dengan periode atau versi pedoman.
- Menyimpan riwayat perubahan.

### Versioning indikator

Jika pedoman berubah, jangan menimpa definisi lama sehingga data periode terdahulu kehilangan konteks.

Gunakan pendekatan versioning atau snapshot yang sesuai agar setiap periode memiliki konfigurasi indikator yang konsisten.

Perubahan indikator untuk periode baru tidak boleh otomatis mengubah indikator, progres, bukti dukung, atau laporan periode lama.

Jika fitur bobot atau perhitungan nilai belum memiliki dasar aturan resmi yang jelas, sediakan konfigurasi yang dapat dikembangkan kemudian tanpa mengaktifkan rumus yang belum dikonfirmasi.

## 12. PROGRAM KERJA

Sediakan modul pengelolaan program kerja.

Atribut yang dapat digunakan:

- ID.
- Periode ZI.
- Organisasi pemilik.
- Area dan indikator terkait.
- Nama program.
- Deskripsi.
- Tujuan.
- Penanggung jawab.
- Tanggal mulai.
- Target selesai.
- Status.
- Catatan.
- Timestamp.

Fitur:

- Membuat program kerja.
- Mengubah program kerja sesuai permission.
- Menetapkan area atau indikator.
- Menetapkan penanggung jawab.
- Menentukan target waktu.
- Melihat daftar dan detail program.
- Melihat rencana aksi yang terkait.
- Melihat bukti dukung yang terkait.
- Memantau progres.
- Mengarsipkan program dengan tetap menjaga riwayat.

Program kerja harus memiliki relasi yang jelas dengan organisasi dan periode.

Validasi agar pengguna tidak dapat menghubungkan program dengan periode atau organisasi yang tidak sesuai kewenangannya.

## 13. RENCANA AKSI

Sediakan pengelolaan rencana aksi yang terkait dengan program kerja.

Atribut yang dapat digunakan:

- ID.
- Program kerja.
- Judul rencana aksi.
- Deskripsi.
- Target keluaran.
- Tanggal mulai.
- Tenggat waktu.
- Penanggung jawab.
- Status pelaksanaan.
- Persentase progres jika relevan.
- Catatan hambatan.
- Timestamp.

Status pelaksanaan harus dibedakan dari status verifikasi bukti dukung.

Gunakan alur status yang jelas, misalnya:

- Belum dimulai.
- Sedang berjalan.
- Selesai.
- Ditunda.
- Dibatalkan atau diarsipkan apabila diperlukan.

Status akhir harus mengikuti kebutuhan bisnis yang dikonfirmasi.

Jangan menyamakan status “selesai” dengan “telah diverifikasi” atau “telah memenuhi substansi ZI”.

Sediakan:

- Daftar rencana aksi.
- Detail rencana aksi.
- Filter berdasarkan program, area, status, dan penanggung jawab.
- Informasi tenggat waktu.
- Indikator keterlambatan.
- Riwayat perubahan status.
- Ringkasan bukti dukung terkait.

Hindari persentase progres yang dibuat-buat. Jika progres dihitung dari jumlah rencana aksi, jelaskan rumusnya dan tampilkan konteksnya.

## 14. PENUGASAN DAN TANGGUNG JAWAB

Sediakan mekanisme penugasan pengguna untuk:

- Program kerja.
- Rencana aksi.
- Pengumpulan bukti dukung.
- Verifikasi.
- Tindak lanjut revisi.

Simpan identitas pihak yang memberikan tugas, pihak yang menerima tugas, organisasi terkait, waktu penugasan, dan status penugasan sesuai kebutuhan.

Pastikan penugasan tidak memberikan permission global secara otomatis.

Penugasan harus memiliki cakupan yang jelas dan tidak boleh mengizinkan pengguna mengakses data di luar organisasi atau periode yang relevan.

Sediakan tampilan tugas saya atau daftar pekerjaan pengguna agar setiap orang dapat melihat tanggung jawab yang diberikan kepadanya.

## 15. REPOSITORI BUKTI DUKUNG

Implementasikan repositori bukti dukung yang terintegrasi dengan program kerja, rencana aksi, indikator, dan periode.

Metadata bukti dukung dapat meliputi:

- Judul.
- Deskripsi.
- Kategori atau jenis bukti.
- Organisasi pemilik.
- Periode.
- Area dan indikator terkait.
- Program kerja atau rencana aksi terkait.
- Pengunggah.
- Waktu unggah.
- Status verifikasi.
- Versi berkas.
- Catatan.
- Riwayat revisi.

Sediakan fitur:

- Unggah bukti dukung.
- Melihat metadata.
- Mengunduh berkas sesuai otorisasi.
- Memperbarui metadata.
- Mengganti berkas melalui versi baru.
- Melihat riwayat versi.
- Menelusuri bukti berdasarkan filter.
- Melihat status verifikasi.
- Melihat catatan revisi.

### Keamanan berkas

- Simpan berkas internal pada storage privat menggunakan Laravel Filesystem.
- Jangan menaruh berkas internal pada direktori publik hanya agar mudah diakses.
- Jangan menampilkan URL publik permanen untuk bukti dukung internal.
- Gunakan endpoint unduh yang memeriksa autentikasi, permission, organisasi, dan keterkaitan data.
- Validasi tipe dan ukuran berkas di server.
- Gunakan nama berkas penyimpanan yang aman.
- Cegah path traversal dan akses ke file arbitrer.
- Jangan mempercayai ekstensi berkas atau MIME type dari browser tanpa validasi server.
- Pertimbangkan pembatasan jenis file yang diizinkan.
- Jangan menghapus versi lama secara otomatis ketika pengguna mengunggah revisi baru.
- Jangan memberikan akses berkas hanya karena seseorang mengetahui ID bukti dukung.

Berkas dan metadata harus memiliki hubungan yang konsisten. Riwayat verifikasi harus dapat dilacak ke versi bukti dukung yang diperiksa.

## 16. VERIFIKASI BUKTI DUKUNG

Implementasikan proses verifikasi bukti dukung yang nyata, terintegrasi dengan backend, dan dapat ditelusuri.

Status yang dapat dipertimbangkan:

- Menunggu verifikasi.
- Sedang diverifikasi.
- Diterima.
- Perlu revisi.
- Ditolak.
- Diajukan kembali.

Sesuaikan status final dengan kebutuhan proses bisnis yang dikonfirmasi.

Fitur verifikasi:

- Melihat bukti dukung yang menjadi tanggung jawab verifikator.
- Melihat metadata dan berkas sesuai izin.
- Memberikan keputusan.
- Menambahkan catatan.
- Meminta revisi.
- Melihat riwayat keputusan.
- Melihat versi bukti yang pernah diperiksa.
- Menampilkan siapa yang memverifikasi dan kapan tindakan dilakukan.

Validasi bahwa hanya pengguna dengan kewenangan yang tepat dapat memverifikasi bukti.

Pengunggah tidak boleh otomatis memiliki kewenangan memverifikasi bukti yang sama apabila aturan pemisahan tugas mengharuskannya dipisahkan.

Catatan revisi harus tersimpan sebagai riwayat, bukan ditimpa tanpa jejak.

Jangan menyamakan keputusan administratif atas bukti dengan penilaian substantif atau penetapan predikat ZI.

## 17. VERIFIKASI BERJENJANG: KABUPATEN/KOTA, PROVINSI, DAN PUSAT

Rancang modul verifikasi agar mampu berkembang ke proses berjenjang tanpa menjadikannya kewajiban untuk seluruh organisasi pada tahap MVP.

### 17.1 Prinsip desain

- Verifikasi internal BPS Kabupaten/Kota harus dapat berfungsi secara mandiri.
- Tahap verifikasi tingkat provinsi dapat diaktifkan jika dibutuhkan.
- Tahap verifikasi tingkat pusat dapat diaktifkan jika dibutuhkan.
- Setiap tahap harus mempunyai penanggung jawab dan cakupan organisasi yang jelas.
- Alur harus dapat dikonfigurasi sesuai ketentuan yang telah dikonfirmasi.
- Jangan mengasumsikan bahwa semua jenis bukti atau semua indikator harus melewati tahapan yang sama.

### 17.2 Data verifikasi

Rancang struktur yang dapat menyimpan:

- Definisi tahap verifikasi.
- Urutan tahap.
- Tingkat organisasi yang menjalankan tahap.
- Status aktif.
- Penugasan verifikator.
- Keputusan pada setiap tahap.
- Catatan dan waktu tindakan.
- Riwayat revisi.
- Hubungan dengan bukti dukung dan versinya.

Gunakan desain yang tidak bergantung pada tiga kolom tetap seperti `status_kabupaten`, `status_provinsi`, dan `status_pusat` sebagai satu-satunya mekanisme. Struktur seperti itu akan sulit dikembangkan jika urutan atau jumlah tahap berubah.

Pertimbangkan tabel konfigurasi alur dan tabel instans tahap verifikasi per objek atau proses. Pilih struktur yang paling sesuai dengan kompleksitas MVP dan jelaskan keputusan desainnya.

### 17.3 Konsistensi alur

- Tahap selanjutnya tidak boleh dimulai sebelum prasyaratnya terpenuhi, apabila aturan alur mengharuskannya.
- Keputusan tingkat lebih tinggi tidak boleh menghapus keputusan tingkat sebelumnya.
- Perubahan konfigurasi alur tidak boleh menulis ulang riwayat keputusan yang sudah terjadi.
- Jika bukti diperbarui, tentukan secara eksplisit tahap mana yang perlu diperiksa kembali.
- Jika verifikasi ulang diperlukan, simpan riwayatnya.
- Jangan membuat keputusan otomatis yang tidak didasarkan pada aturan bisnis.
- Jangan mengaktifkan alur provinsi dan pusat pada MVP jika belum diperlukan.

## 18. DASHBOARD BERDASARKAN ROLE DAN ORGANISASI

Buat dashboard yang mengambil data nyata dari basis data, bukan angka statis.

Dashboard harus menyesuaikan permission dan cakupan organisasi pengguna.

### Dashboard pimpinan

Dapat menampilkan:

- Jumlah program kerja.
- Jumlah rencana aksi.
- Rencana aksi yang belum dimulai.
- Rencana aksi yang sedang berjalan.
- Rencana aksi yang selesai.
- Rencana aksi yang terlambat.
- Jumlah bukti dukung berdasarkan status verifikasi.
- Bukti dukung yang perlu revisi.
- Tugas atau hambatan yang perlu perhatian.
- Ringkasan progres per area.

### Dashboard koordinator

Dapat menampilkan:

- Program dan rencana aksi dalam tanggung jawabnya.
- Progres pelaksanaan.
- Daftar penanggung jawab.
- Tenggat waktu.
- Bukti yang menunggu verifikasi.
- Revisi yang belum ditindaklanjuti.

### Dashboard pelaksana

Dapat menampilkan:

- Tugas saya.
- Tenggat waktu terdekat.
- Rencana aksi yang menjadi tanggung jawabnya.
- Bukti dukung yang perlu diunggah.
- Bukti yang perlu diperbaiki.
- Status verifikasi.

### Dashboard verifikator

Dapat menampilkan:

- Antrean verifikasi.
- Bukti yang baru diajukan.
- Bukti yang perlu ditindaklanjuti.
- Riwayat verifikasi.
- Filter sesuai cakupan organisasi dan periode.

### Kesiapan dashboard provinsi dan pusat

Arsitektur harus memungkinkan dashboard agregat lintas organisasi di masa mendatang.

Dashboard provinsi hanya boleh mengagregasi organisasi dalam cakupannya. Dashboard pusat dapat mengagregasi data lintas wilayah jika permission mengizinkan.

Pada MVP, dashboard lintas organisasi dapat ditunda.

Jangan menampilkan angka persentase yang tidak memiliki definisi. Berikan label atau tooltip yang menjelaskan apakah angka merepresentasikan penyelesaian rencana aksi, kelengkapan bukti, status verifikasi, atau ukuran lain.

Gunakan query yang efisien, filter yang jelas, dan penanganan empty state.

## 19. LAPORAN DAN EKSPOR DATA

Sediakan halaman laporan yang mendukung:

- Rekap program kerja.
- Rekap rencana aksi.
- Rekap progres per area.
- Rekap bukti dukung berdasarkan status.
- Daftar bukti yang perlu revisi.
- Rekap pekerjaan yang terlambat.
- Riwayat verifikasi.
- Filter periode, area, status, dan penanggung jawab.
- Ekspor CSV untuk kebutuhan pengolahan lanjutan.

Jika format ekspor tambahan dibutuhkan, implementasikan sesuai prioritas dan kompatibilitas dependensi.

Pastikan ekspor menggunakan filter dan cakupan otorisasi yang sama dengan halaman terkait.

Jangan membocorkan data lintas organisasi melalui ekspor.

Untuk fitur lintas tingkat yang dikembangkan kemudian, sediakan kemungkinan laporan agregat berdasarkan hierarki organisasi.

Jangan mengklaim telah menghasilkan laporan resmi evaluasi ZI jika format, rumus, dan ketentuannya belum dikonfirmasi.

## 20. AUDIT TRAIL DAN RIWAYAT AKTIVITAS

Implementasikan audit trail untuk aktivitas penting.

Aktivitas yang perlu dicatat dapat mencakup:

- Perubahan data pengguna dan organisasi.
- Perubahan role dan permission.
- Perubahan konfigurasi periode dan indikator.
- Pembuatan atau perubahan program kerja.
- Perubahan rencana aksi.
- Pengunggahan atau penggantian bukti dukung.
- Keputusan verifikasi.
- Permintaan revisi.
- Perubahan konfigurasi alur verifikasi.
- Pengarsipan data penting.

Informasi audit dapat meliputi:

- Pengguna yang melakukan tindakan.
- Organisasi dalam konteks tindakan.
- Jenis tindakan.
- Entitas terkait.
- Waktu tindakan.
- Ringkasan perubahan yang relevan.
- Identitas request atau metadata tambahan jika diperlukan.

Jangan mencatat kata sandi, token autentikasi, kredensial, atau isi rahasia ke audit log.

Batasi akses terhadap audit trail. Perubahan atau penghapusan catatan audit harus dikendalikan dengan ketat.

Jangan membuat audit trail sekadar tabel yang tidak pernah diisi. Integrasikan pencatatan ke proses bisnis yang relevan.

## 21. PEDOMAN UI/UX

Gunakan UI/UX Pro Max secara aktif dalam merancang dan mengimplementasikan tampilan aplikasi.

Sebelum membangun halaman:

1. Baca instruksi skill.
2. Tentukan design system yang cocok untuk aplikasi administrasi pemerintahan.
3. Tentukan warna, tipografi, spacing, radius, elevasi, dan aturan penggunaan komponen.
4. Tentukan pola navigasi serta hierarki informasi.
5. Terapkan sistem desain tersebut secara konsisten pada seluruh halaman.

### Arah visual

Aplikasi harus terasa:

- Profesional.
- Modern.
- Terstruktur.
- Kredibel.
- Mudah dipahami.
- Efisien untuk pekerjaan administratif sehari-hari.
- Nyaman digunakan dalam sesi kerja yang panjang.

Hindari tampilan yang terlalu dekoratif, terlalu banyak gradient, animasi yang tidak diperlukan, atau kartu statistik yang memenuhi seluruh halaman tanpa membantu pekerjaan pengguna.

Jangan membuat semua halaman hanya berupa tabel dan form yang tidak memiliki hierarki visual.

### Layout utama

Implementasikan:

- Sidebar navigasi yang konsisten.
- Header yang menampilkan konteks halaman dan informasi pengguna.
- Breadcrumb pada halaman bertingkat apabila bermanfaat.
- Navigasi responsif.
- Area konten dengan lebar dan spacing yang teratur.
- Judul halaman yang informatif.
- Aksi utama yang mudah ditemukan.
- Filter dan pencarian yang relevan.
- Tabel yang nyaman digunakan.
- Form dengan label, validasi, dan petunjuk yang jelas.
- Empty state yang memberikan arahan.
- Loading state.
- Error state.
- Toast atau notifikasi tindakan yang sesuai.
- Dialog konfirmasi untuk tindakan berisiko.

### Tabel dan formulir

Untuk tabel administratif, pertimbangkan:

- Pagination dari backend.
- Search dan filter.
- Sorting jika relevan.
- Status badge yang konsisten.
- Aksi baris sesuai permission.
- Tampilan mobile yang tetap dapat digunakan.
- Penanganan hasil kosong.

Untuk formulir:

- Gunakan validasi sisi server.
- Tampilkan pesan kesalahan dekat field terkait.
- Pertahankan nilai input ketika terjadi kesalahan validasi jika memungkinkan.
- Hindari meminta data yang tidak diperlukan.
- Kelompokkan field berdasarkan konteks.
- Bedakan aksi simpan, batal, arsip, dan hapus.
- Gunakan konfirmasi sebelum tindakan destruktif.

### Aksesibilitas

Perhatikan:

- Kontras warna.
- Ukuran teks.
- Navigasi keyboard.
- Focus state.
- Label input.
- Struktur heading.
- Nama yang dapat diakses untuk tombol ikon.
- Indikator status yang tidak hanya mengandalkan warna.

### Konsistensi implementasi

Gunakan komponen bersama untuk pola yang berulang, seperti:

- Page header.
- Breadcrumb.
- Data table.
- Filter bar.
- Pagination.
- Form field.
- Status badge.
- Dialog konfirmasi.
- Empty state.
- Loading state.
- Notifikasi.

Jangan membuat komponen abstrak berlebihan untuk elemen yang hanya digunakan sekali dan tidak memiliki manfaat reuse.

## 22. KEAMANAN APLIKASI

Keamanan merupakan persyaratan wajib.

Implementasikan:

- Validasi input di backend.
- Autentikasi pada route yang memerlukan login.
- Authorization pada setiap operasi sensitif.
- Pembatasan akses berbasis organisasi.
- CSRF protection sesuai mekanisme Laravel.
- Perlindungan terhadap mass assignment melalui konfigurasi model yang tepat.
- Validasi unggahan berkas.
- Penyimpanan berkas privat.
- Penanganan error tanpa membocorkan stack trace pada production.
- Pengelolaan secret melalui environment.
- Penggunaan query Eloquent atau parameter binding yang aman.
- Pengendalian akses terhadap laporan dan ekspor.
- Pengendalian operasi administratif.
- Rate limiting untuk endpoint yang relevan.
- Logging yang tidak menyimpan kredensial atau token.
- Penggunaan transaksi pada operasi multi-tabel yang membutuhkan konsistensi.

Jangan mengandalkan validasi frontend sebagai perlindungan keamanan.

Jangan menonaktifkan middleware keamanan untuk membuat fitur terlihat berfungsi.

Jangan menambahkan kredensial nyata ke repository.

Pastikan file internal tidak dapat diakses langsung melalui URL publik.

## 23. VERSIONING PEDOMAN DAN KONSISTENSI DATA HISTORIS

Perubahan pedoman ZI, indikator, dan konfigurasi verifikasi tidak boleh merusak data historis.

Rancang mekanisme versioning atau snapshot yang sesuai untuk:

- Periode ZI.
- Definisi area dan indikator.
- Konfigurasi rencana kerja yang relevan.
- Konfigurasi tahap verifikasi.
- Hasil verifikasi.
- Riwayat bukti dukung.

Jika definisi indikator berubah pada periode baru, data periode lama harus tetap merujuk ke definisi yang berlaku pada periode tersebut.

Jika organisasi berpindah atau berubah struktur, jangan otomatis memindahkan kepemilikan seluruh data lama tanpa kebijakan yang jelas.

Jangan menggunakan soft delete sebagai satu-satunya strategi untuk semua jenis data. Pilih mekanisme pengarsipan, versioning, dan penghapusan berdasarkan karakteristik entitas.

Jaga integritas referensial dan jelaskan kebijakan perubahan data historis dalam dokumentasi.

## 24. DESAIN BASIS DATA DAN KONVENSI KODE

Gunakan MySQL dengan InnoDB.

Rancang skema relasional yang normal dan memiliki relasi yang jelas.

Entitas yang perlu dipertimbangkan antara lain:

- Organizations.
- Users.
- Periode ZI.
- Versi pedoman.
- Area.
- Indikator dan subindikator.
- Program kerja.
- Rencana aksi.
- Penugasan.
- Bukti dukung.
- Versi bukti dukung.
- Konfigurasi alur verifikasi.
- Tahap verifikasi.
- Riwayat keputusan verifikasi.
- Tindak lanjut revisi.
- Audit trail.
- Tabel Spatie Laravel Permission.

Nama tabel dan kolom di atas merupakan panduan konseptual. Sesuaikan dengan struktur codebase dan kebutuhan relasi sebenarnya.

Terapkan:

- Primary key dan foreign key.
- Unique constraint yang relevan.
- Indeks pada kolom filter dan relasi yang sering digunakan.
- Tipe data yang sesuai.
- Timestamp.
- Soft deletes hanya jika memang sesuai.
- Aturan penghapusan dan pengarsipan yang eksplisit.
- Transaksi untuk proses multi-langkah.
- Relasi Eloquent yang lengkap.

Hindari menyimpan data relasional penting sebagai JSON hanya demi mempermudah implementasi.

JSON dapat digunakan untuk metadata fleksibel jika ada alasan yang kuat, tetapi bukan sebagai pengganti desain relasional yang dibutuhkan untuk query, constraint, dan audit.

Gunakan migration untuk perubahan skema.

Jangan mengubah struktur database production secara manual tanpa migration yang dapat ditelusuri.

Buat seeder untuk data awal yang aman, termasuk role dan permission, serta data referensi yang telah diverifikasi.

Jangan memasukkan nilai bobot atau indikator yang belum dikonfirmasi sebagai aturan resmi.

## 25. KINERJA DAN SKALABILITAS

Aplikasi harus dirancang agar tetap mudah dikembangkan ketika jumlah pengguna, bukti dukung, rencana aksi, dan organisasi bertambah.

Terapkan:

- Pagination server-side.
- Eager loading untuk menghindari N+1.
- Query yang hanya mengambil kolom yang diperlukan.
- Indeks berdasarkan pola akses yang nyata.
- Filter dan sorting yang efisien.
- Penyimpanan berkas melalui Laravel Filesystem.
- Pemisahan service sesuai kebutuhan.
- Pembatasan data berdasarkan cakupan organisasi sejak query awal.
- Transaksi untuk operasi penting.
- Cache hanya jika diperlukan dan invalidasinya dapat dijaga.
- Queue untuk pekerjaan berat jika memang dibutuhkan.
- Optimasi agregasi dashboard.

Jangan mengambil seluruh data ke browser lalu melakukan pagination atau filtering di frontend apabila volume data dapat berkembang.

Jangan menambahkan cache, queue, microservices, atau infrastruktur kompleks tanpa kebutuhan nyata.

Prioritaskan modularitas dan konsistensi data sebelum optimasi yang prematur.

## 26. FITUR NOTIFIKASI DAN PENGINGAT

Jika sesuai dengan prioritas MVP, sediakan notifikasi internal untuk:

- Penugasan baru.
- Bukti dukung yang perlu direvisi.
- Verifikasi yang menunggu tindakan.
- Tenggat waktu yang mendekat.
- Perubahan status yang relevan.

Mulai dari mekanisme notifikasi yang sederhana dan sesuai dengan fasilitas Laravel yang tersedia.

Jangan mengklaim pengiriman email, WhatsApp, atau notifikasi eksternal sudah berfungsi jika integrasi dan kredensialnya belum tersedia.

Jika integrasi eksternal belum disiapkan, dokumentasikan kebutuhan konfigurasi dan sediakan notifikasi internal sebagai alternatif awal.

## 27. PENGUJIAN DAN VALIDASI IMPLEMENTASI

**Jangan membuat automated test suite. Pengujian aplikasi akan dilakukan secara manual oleh saya sebagai pengguna.**

Karena itu:

- Jangan membuat suite PHPUnit atau Pest.
- Jangan membuat suite React Testing Library.
- Jangan menghabiskan waktu mengimplementasikan automated integration atau end-to-end tests.
- Jika repository sudah memiliki test bawaan, jangan menghapusnya secara membabi buta; cukup jangan menambahkan suite otomatis baru sebagai bagian pekerjaan ini.
- Tetap implementasikan validasi backend, authorization, penanganan error, dan perlindungan keamanan secara lengkap.
- Jangan mengklaim fitur sudah lolos pengujian fungsional hanya karena kodenya telah dibuat.

Kamu tetap harus melakukan pemeriksaan teknis yang tidak memerlukan pembuatan automated test suite, misalnya:

- Memastikan dependensi dapat dipasang jika lingkungan memungkinkan.
- Memastikan migration dan seeder konsisten.
- Memastikan TypeScript dan proses build frontend tidak menghasilkan error.
- Memeriksa error sintaks atau import yang dapat dideteksi.
- Memeriksa kompatibilitas route, model, dan komponen.
- Menjalankan pemeriksaan statis yang sudah tersedia jika aman dan relevan.
- Memperbaiki error yang ditemukan.

Jangan menjalankan operasi yang merusak data hanya untuk memeriksa migration.

### Checklist pengujian manual

Sediakan checklist yang dapat saya gunakan untuk menguji:

1. Login dan logout.
2. Akses halaman berdasarkan role.
3. Pembatasan akses berdasarkan organisasi.
4. Pengelolaan pengguna dan permission.
5. Pengelolaan periode.
6. Pengelolaan area dan indikator.
7. Pembuatan program kerja.
8. Pembuatan dan perubahan rencana aksi.
9. Penugasan pelaksana.
10. Unggah dan unduh bukti dukung.
11. Penolakan akses unduhan tanpa izin.
12. Verifikasi bukti dukung.
13. Permintaan revisi dan pengajuan ulang.
14. Riwayat versi bukti dukung.
15. Dashboard dan laporan.
16. Ekspor data.
17. Audit trail.
18. Validasi input dan penanganan kesalahan.
19. Pengujian batas akses melalui URL dan request langsung.
20. Konsistensi data historis setelah konfigurasi berubah.

Checklist harus menjelaskan langkah, hasil yang diharapkan, dan status yang dapat diisi pengguna.

## 28. README DAN DOKUMENTASI

Buat atau perbarui README dengan informasi aktual mengenai:

- Deskripsi aplikasi.
- Stack teknologi.
- Persyaratan environment.
- Instalasi backend.
- Instalasi frontend.
- Konfigurasi environment.
- Konfigurasi koneksi MySQL.
- Cara membuat database sesuai setup lokal.
- Cara menjalankan migration.
- Cara menjalankan seeder.
- Cara menjalankan aplikasi.
- Cara membangun frontend.
- Cara membuat akun awal dengan aman.
- Cara mengatur role dan permission.
- Cara menambahkan organisasi.
- Cara mengatur cakupan organisasi pengguna.
- Penjelasan modul.
- Penjelasan alur verifikasi yang benar-benar sudah diimplementasikan.
- Penyimpanan berkas bukti dukung.
- Checklist pengujian manual.
- Batasan MVP.
- Rencana pengembangan menuju provinsi dan pusat.
- Cara mengonfigurasi plugin atau skill jika dokumentasi environment mengharuskannya.

Semua instruksi harus sesuai dengan file, perintah, dan dependensi yang benar-benar ada.

Jangan menulis dokumentasi yang mengasumsikan fitur sudah selesai jika implementasinya belum tersedia.

## 29. URUTAN PELAKSANAAN

Ikuti urutan berikut, tetapi sesuaikan jika kondisi repository memerlukan pendekatan lain.

### Fase 1 — Inspeksi dan persiapan

- Periksa repository dan versi dependensi.
- Baca petunjuk proyek.
- Siapkan dan verifikasi Ponytail serta UI/UX Pro Max.
- Gunakan UI/UX Pro Max untuk menetapkan design system.

### Fase 2 — Fondasi aplikasi

- Siapkan konfigurasi backend dan frontend.
- Pastikan autentikasi berjalan.
- Integrasikan Spatie Laravel Permission.
- Buat organisasi hierarkis.
- Hubungkan pengguna dengan organisasi.
- Terapkan pola authorization berdasarkan permission dan cakupan organisasi.

### Fase 3 — Master data dan periode

- Implementasikan periode ZI.
- Implementasikan master area dan indikator.
- Siapkan versioning pedoman dan data historis.

### Fase 4 — Proses kerja

- Implementasikan program kerja.
- Implementasikan rencana aksi.
- Implementasikan penugasan dan daftar pekerjaan pengguna.

### Fase 5 — Bukti dukung dan verifikasi

- Implementasikan penyimpanan berkas privat.
- Implementasikan metadata dan versi bukti dukung.
- Implementasikan proses verifikasi dan revisi.
- Implementasikan pencatatan riwayat.

### Fase 6 — Dashboard dan pelaporan

- Implementasikan dashboard berbasis data aktual.
- Implementasikan filter dan laporan.
- Implementasikan ekspor data sesuai permission.

### Fase 7 — Audit, UI/UX, dan keamanan

- Integrasikan audit trail.
- Rapikan antarmuka menggunakan design system.
- Pastikan responsivitas dan aksesibilitas.
- Periksa batas akses lintas organisasi.
- Periksa penanganan error dan validasi.

### Fase 8 — Penyelesaian

- Jalankan pemeriksaan teknis yang aman.
- Perbaiki error yang ditemukan.
- Buat README.
- Buat checklist pengujian manual.
- Dokumentasikan fitur yang selesai dan yang masih tertunda.

Jangan berhenti setelah menyelesaikan satu fase jika waktu dan lingkungan masih memungkinkan untuk melanjutkan fase berikutnya.

## 30. KRITERIA KEBERHASILAN MVP

MVP dinyatakan selesai secara implementasi apabila:

- Aplikasi berjalan dengan Laravel, MySQL, Inertia.js, React, TypeScript, Vite, dan Tailwind CSS.
- Autentikasi berfungsi.
- Spatie Laravel Permission terintegrasi.
- Pengguna terhubung dengan organisasi.
- Organisasi mendukung relasi induk dan anak.
- Pembatasan akses diterapkan pada backend.
- Data periode, area, dan indikator dapat dikelola.
- Program kerja dan rencana aksi dapat dibuat dan ditelusuri.
- Penugasan dapat dikelola.
- Bukti dukung dapat diunggah dan diakses secara privat sesuai kewenangan.
- Verifikasi dan revisi memiliki riwayat.
- Dashboard mengambil data aktual.
- Laporan dan ekspor mengikuti batas akses.
- Audit trail mencatat tindakan penting.
- Perubahan pedoman tidak merusak data historis.
- Desain mendukung ekspansi ke tingkat provinsi dan pusat tanpa mewajibkan fitur tersebut aktif pada MVP.
- README dan checklist pengujian manual tersedia.
- Pemeriksaan teknis yang dilakukan dan hasilnya didokumentasikan secara jujur.

## 31. ATURAN KERJA FINAL

Laksanakan instruksi berikut selama pengembangan:

1. **Langsung implementasikan aplikasi pada repository**, bukan hanya menulis rencana.
2. Gunakan Ponytail dan UI/UX Pro Max sesuai kemampuan aktual environment setelah instalasi dan verifikasi.
3. Pertahankan stack teknologi yang telah ditentukan.
4. Prioritaskan implementasi yang benar-benar berfungsi, bukan sekadar tampilan.
5. Jangan mengganti MySQL dengan PostgreSQL.
6. Jangan menggunakan Filament atau Laravel Shield.
7. Jangan membuat automated test suite; pengujian fungsional akan dilakukan manual oleh saya.
8. Tetap implementasikan validasi, authorization, keamanan, dan penanganan error secara lengkap.
9. Jangan mereset database atau menghapus data secara destruktif tanpa izin.
10. Jangan mengarang regulasi, indikator resmi, bobot, rumus penilaian, atau kewenangan organisasi.
11. Jangan memberikan akses lintas organisasi secara otomatis.
12. Jangan membuat data dummy atau angka dashboard seolah-olah merupakan data aktual.
13. Jangan mengklaim fitur, integrasi, plugin, skill, build, atau pemeriksaan berhasil jika belum diverifikasi.
14. Jika ada informasi proses bisnis yang belum tersedia, gunakan desain yang fleksibel dan dokumentasikan asumsi. Jangan menghentikan seluruh implementasi hanya karena detail nonkritis belum ditentukan.
15. Jika ada keputusan yang berpotensi destruktif, berisiko terhadap data, atau memerlukan kredensial yang belum tersedia, jangan melanjutkannya tanpa otorisasi.
16. Lakukan perubahan file nyata, perbaiki error, dan lanjutkan sampai fitur MVP yang dapat diimplementasikan telah selesai.
17. Jangan menunggu persetujuan untuk keputusan teknis biasa yang aman dan dapat dibalik.
18. Laporkan hasil berdasarkan kondisi aktual repository.

## 32. FORMAT LAPORAN AKHIR

Setelah implementasi, berikan laporan ringkas tetapi lengkap yang memuat:

- Ringkasan aplikasi yang berhasil diimplementasikan.
- Modul yang telah selesai.
- Modul yang baru sebagian selesai.
- Fitur yang belum diimplementasikan.
- Struktur arsitektur dan basis data utama.
- Implementasi hierarki organisasi dan batas aksesnya.
- Status instalasi serta penggunaan Ponytail.
- Status instalasi serta penggunaan UI/UX Pro Max.
- Pemeriksaan teknis yang benar-benar dijalankan beserta hasilnya.
- Masalah yang masih ada.
- Langkah instalasi dan menjalankan aplikasi.
- Langkah konfigurasi database dan akun awal.
- Lokasi dokumentasi serta checklist pengujian manual.
- Rekomendasi langkah berikutnya untuk pengembangan tingkat provinsi dan pusat.

Jangan menyatakan aplikasi telah selesai sepenuhnya jika masih terdapat fitur penting yang belum berfungsi.

**Mulai sekarang:** periksa repository, siapkan plugin dan skill yang diwajibkan, tetapkan design system, kemudian langsung implementasikan ZI-TERPADU sesuai instruksi di atas.
