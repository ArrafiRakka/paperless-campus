# Pengujian

## Perintah

```powershell
php artisan test
php artisan route:list
php artisan migrate:status
php artisan view:cache
```

Test suite menggunakan SQLite in-memory sesuai `phpunit.xml`; `RefreshDatabase` tidak menyentuh database `.env` Laragon.

## Skenario otomatis

Login/logout dan role guard; halaman server-rendered; penyimpanan draft/submission; upload PDF private; batas akses pemilik; revisi dengan note; approval dua tahap; dokumen final internal privat; sanity-check rumus skenario/toleransi dan zero baseline.

PDF satu halaman telah diuji header `%PDF-1.4`, hash, penyimpanan privat dan unduh berotorisasi. Race-condition konkurensi approval dengan MySQL masih perlu test tambahan.

## Hasil terakhir yang benar-benar dijalankan

- `php artisan test`: 11 tests, 76 assertions lulus.
- `php artisan migrate --force`: lima migration aplikasi selesai pada SQLite yang dikonfigurasi; tidak ada reset.
- `php artisan migrate:status`: semua lima migration berstatus Ran.
- `php artisan route:list`: 31 route.
- `php artisan view:cache`: berhasil.
- `node --check resources/js/app.js`: berhasil; Vite build belum dapat dijalankan karena `node_modules` tidak ada.

