# PaperLess Campus

Aplikasi web administrasi digital untuk satu unit layanan kampus. Cakupan: Surat Keterangan Aktif Kuliah, Surat Izin Kegiatan, dan Peminjaman Fasilitas. UI berbahasa Indonesia; autentikasi, role, pengajuan, approval, timeline, arsip, konfigurasi, serta metrik transaksi memakai Laravel dan database.

## Status teknis

- Framework terpasang: Laravel 13.42.0; PHP CLI lokal: 8.3.30. Versi Laravel dipertahankan sesuai project.
- Antarmuka memakai Blade SSR dengan CSS/JavaScript vanilla dan file statis ringan di `public/`.
- `.env` lokal saat diaudit memakai SQLite. Migration baru aditif; database tidak di-reset.
- Dokumen final dibuat satu kali sebagai PDF satu halaman oleh generator internal tanpa dependency eksternal. Dokumen tersebut untuk simulasi administrasi dan bukan tanda tangan elektronik tersertifikasi.
- Nilai CO₂e adalah estimasi counterfactual/proyeksi, bukan pembacaan listrik server atau hasil audit.

## Menjalankan dengan Laragon (Windows)

1. Jalankan Apache dan MySQL hanya bila memilih MySQL. Pastikan PHP Laragon 8.3+ aktif di PATH.
2. Dari terminal project: `composer install` (vendor saat ini sudah ada). Salin `.env.example` ke `.env` bila belum mempunyai `.env`; jangan menimpa file `.env` yang berisi konfigurasi lokal.
3. Untuk SQLite: pastikan `DB_CONNECTION=sqlite` dan `database/database.sqlite` ada. Project lokal saat ini sudah memakai file tersebut.
4. Untuk MySQL Laragon: buat database `paperless_campus` lewat HeidiSQL/phpMyAdmin, lalu isi `DB_CONNECTION=mysql`, host/port/database/user/password sesuai konfigurasi lokal Anda.
5. Jalankan migration tanpa reset: `php artisan migrate`.
6. Isi data demo lokal (idempoten): `php artisan db:seed`. Seed akun demo dan submission hanya bila tabel submission belum berisi data.
7. Buka Laragon Auto Virtual Host jika domain lokal sudah dibuat, atau jalankan `php artisan serve` lalu buka `http://127.0.0.1:8000`.

Jika Anda mengubah `APP_KEY`/konfigurasi, gunakan `php artisan config:clear`. Jangan bagikan `.env` atau gunakan password demo di produksi.

## Akun demo lokal

Seeder membuat:

| Peran | Email | Kata sandi |
|---|---|---|
| Pengaju | `pengaju@paperless.test` | `Perbaiki error Laravel pada project PaperLess Campus.

Saat membuka `http://127.0.0.1:8000/pengajuan/4` setelah mengunggah dokumen PDF, muncul:

**ParseError: syntax error, unexpected token "endforeach", expecting end of file**

Lokasi: `resources/views/submissions/show.blade.php`, sekitar baris 5.

Tugas:
1. Baca seluruh isi `show.blade.php`.
2. Periksa semua pasangan directive Blade, termasuk `@foreach/@endforeach`, `@forelse/@empty/@endforelse`, `@if/@endif`, dan directive lainnya.
3. Perbaiki kesalahan syntax yang menyebabkan ParseError.
4. Rapikan HTML dan Blade menjadi beberapa baris dengan indentasi yang konsisten agar mudah diperiksa.
5. Pertahankan seluruh tampilan, informasi, dan fungsionalitas halaman detail pengajuan.
6. Pastikan lampiran PDF dapat ditampilkan sebagai tautan atau diunduh sesuai hak akses pengguna.
7. Jalankan `php artisan view:clear`, `php artisan view:cache`, dan `php artisan test`.
8. Periksa apakah ada kesalahan Blade serupa pada halaman lain.

Jangan membuat ulang project, jangan menghapus data database, dan jangan melakukan `migrate:fresh`.

Implementasikan perbaikannya langsung, lalu laporkan file yang diubah dan hasil pengujian.` |
| Verifikator | `verifikator@paperless.test` | `DemoPaperless!2026` |
| Admin unit | `admin@paperless.test` | `DemoPaperless!2026` |

Akun ini hanya untuk demo lokal. Tidak ada pemilih role mandiri di layar login; role berasal dari rekaman user.

## Cara demo singkat

1. Login Pengaju, buat pengajuan salah satu dari tiga layanan, simpan draf atau kirim.
2. Logout, login Verifikator, cari antrean dan setujui tahap atau minta revisi dengan catatan.
3. Login kembali Pengaju untuk melihat timeline/revisi. Untuk menguji dua tahap, ubah konfigurasi layanan event sebagai Admin menjadi 2 tahap.
4. Setelah semua tahap disetujui, cek Arsip Digital dan unduh keluaran internal privat. PDF privat simulasi, bukan dokumen bertanda tangan tersertifikasi.
5. Lihat Dashboard Dampak dan Metodologi; transaksi selesai dipisahkan dari panel proyeksi 3.000 pengajuan/tahun.

## Perintah QA

```powershell
php artisan test
php artisan route:list
php artisan migrate:status
php artisan view:cache
```

Vite build memerlukan `npm install` lebih dahulu. Halaman Laravel memakai aset statis yang sudah disediakan di `public/paperless.css` dan `public/paperless.js`, jadi dapat dibuka tanpa build. Jangan jalankan `migrate:fresh` pada database berisi data.

Queue lokal dapat memakai `QUEUE_CONNECTION=sync`; jika menggunakan queue database, jalankan `php artisan queue:work`. Aplikasi saat ini menyelesaikan workflow sinkron dan tidak membutuhkan worker.

## Dokumentasi teknis

- [Arsitektur](docs/ARCHITECTURE.md)
- [Metodologi CO₂e](docs/CO2_METHOD.md)
- [Bukti GSE](docs/GSE_EVIDENCE.md)
- [Testing](docs/TESTING.md)
- [Rencana pengukuran performa](docs/PERFORMANCE_TEST_PLAN.md)
- [Alignment laporan](docs/REPORT_ALIGNMENT.md)

