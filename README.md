# PaperLess Campus

PaperLess Campus adalah aplikasi administrasi digital untuk unit layanan kampus. Aplikasi ini mendukung pengajuan surat keterangan aktif kuliah, izin kegiatan, dan peminjaman fasilitas. Antarmuka menggunakan Bahasa Indonesia.

## Fitur

- Autentikasi berbasis sesi dan akses sesuai peran: Pengaju, Verifikator, dan Admin.
- Pembuatan, pengiriman, pelacakan, revisi, dan verifikasi pengajuan.
- Persetujuan satu hingga tiga tahap, dengan catatan untuk revisi atau penolakan.
- Lampiran PDF privat dengan batas ukuran 2 MB.
- Pembuatan PDF internal setelah seluruh tahap persetujuan selesai, serta arsip dengan akses terotorisasi.
- Pengelolaan pengguna, konfigurasi layanan, baseline, dan faktor emisi.
- Dashboard dampak yang memisahkan transaksi selesai dari proyeksi tahunan.

## Teknologi dan status

- Laravel 13.42.0 dan PHP 8.3.30 pada lingkungan pengembangan yang diaudit.
- Blade server-side, CSS dan JavaScript ringan, serta aset statis di `public/`.
- Database lokal saat ini menggunakan SQLite pada `database/database.sqlite`.
- PDF final dibuat oleh generator internal satu halaman. Dokumen ini untuk simulasi dan bukan dokumen resmi atau tanda tangan elektronik tersertifikasi.
- Nilai CO2e adalah estimasi berdasarkan faktor dan asumsi yang tercatat. Bukan pengukuran listrik server atau audit karbon. Proyeksi 3.000 pengajuan per tahun ditampilkan terpisah dari transaksi database.

## Menjalankan di Laragon

Pastikan PHP Laragon dan Composer tersedia di terminal. Dari folder project:

```powershell
composer install
php artisan migrate
php artisan db:seed
php artisan serve
```

Buka `http://127.0.0.1:8000`. Project lokal menggunakan SQLite, sehingga tidak perlu membuat database MySQL untuk mencoba aplikasi.

Jika file `.env` belum ada, salin `.env.example` menjadi `.env`, lalu atur `APP_KEY` dan koneksi database sesuai kebutuhan. Jangan menimpa `.env` yang sudah berisi konfigurasi lokal. Untuk MySQL Laragon, buat database terlebih dahulu melalui HeidiSQL atau phpMyAdmin, lalu sesuaikan `DB_CONNECTION`, host, port, nama database, dan kredensial di `.env`.

Migration aplikasi bersifat aditif. Gunakan `php artisan migrate` untuk menerapkannya. Jangan gunakan `migrate:fresh` pada database yang berisi data. Seeder demo bersifat idempoten dan tidak membuat ulang contoh pengajuan jika sudah ada pengajuan.

## Konfigurasi Railway

Tambahkan variabel berikut pada Variables service Railway, lalu deploy ulang:

| Variabel | Nilai |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://paperless-campus-production.up.railway.app` |
| `TRUSTED_PROXIES` | `*` |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_SAME_SITE` | `lax` |
| `SESSION_DOMAIN` | Biarkan tidak disetel (cookie khusus host) |

Railway menyediakan `RAILWAY_ENVIRONMENT_NAME`; aplikasi hanya menerima `TRUSTED_PROXIES=*` pada environment production Railway. Di Laragon, proxy tepercaya tidak diaktifkan otomatis dan HTTP localhost tetap didukung. Jangan mengubah atau membuat ulang `APP_KEY` saat memperbaiki HTTPS.

Pastikan layanan memakai database production yang persisten dan jalankan migration normal (`php artisan migrate`) agar tabel `sessions` tersedia. Jangan gunakan SQLite pada filesystem deployment yang tidak persisten untuk data akun/sesi production. Jika aplikasi memakai beberapa replica, semuanya harus memakai database dan `APP_KEY` yang sama.

## Akun demo lokal

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Pengaju | `pengaju@paperless.test` | `DemoPaperless!2026` |
| Verifikator | `verifikator@paperless.test` | `DemoPaperless!2026` |
| Admin unit | `admin@paperless.test` | `DemoPaperless!2026` |

Akun tersebut hanya untuk demo lokal. Peran ditentukan oleh akun di database; pengguna tidak dapat memilih peran sendiri pada halaman login. Jangan gunakan kata sandi demo di lingkungan produksi.

## Alur demo

1. Masuk sebagai Pengaju, buat pengajuan, lalu simpan sebagai draf atau kirim untuk verifikasi.
2. Masuk sebagai Verifikator untuk memeriksa pengajuan. Admin dapat mengubah jumlah tahap persetujuan pada konfigurasi layanan.
3. Untuk revisi, verifikator menambahkan catatan. Pengaju dapat memperbaiki dan mengirim kembali pengajuan.
4. Setelah seluruh tahap disetujui, detail pengajuan menampilkan status selesai dan tautan ke PDF internal pada arsip digital.
5. Buka Dashboard Dampak dan Metodologi untuk melihat asumsi, faktor, transaksi selesai, dan proyeksi tahunan.

## Aset frontend

Aplikasi menyajikan `public/paperless.css` dan `public/paperless.js`, jadi dapat dijalankan tanpa build frontend. Jika ingin menjalankan build Vite:

```powershell
npm install
npm run build
```

Preferensi tema disimpan pada browser. Data pengajuan dan proses bisnis disimpan melalui Laravel dan database.

## Pemeriksaan

```powershell
php artisan test
php artisan route:list
php artisan migrate:status
php artisan view:clear
php artisan view:cache
```

Feature tests menggunakan SQLite in-memory dan tidak mengubah database lokal di `.env`. Pengukuran Lighthouse, Web Vitals, ukuran transfer, dan pengujian konkurensi pada MySQL perlu dilakukan terpisah; hasilnya tidak diklaim di sini.

Workflow aplikasi berjalan sinkron dan tidak memerlukan queue worker untuk demo lokal. Jika konfigurasi queue diubah, ikuti driver yang dipilih pada `.env`.

## Dokumentasi teknis

- [Arsitektur](docs/ARCHITECTURE.md)
- [Metodologi CO2e](docs/CO2_METHOD.md)
- [Bukti Green Software Engineering](docs/GSE_EVIDENCE.md)
- [Rencana dan hasil pengujian](docs/TESTING.md)
- [Rencana pengukuran performa](docs/PERFORMANCE_TEST_PLAN.md)
- [Penyesuaian laporan proyek](docs/REPORT_ALIGNMENT.md)
