# Bukti Green Software Engineering

- Rendering halaman memakai Blade SSR dan JavaScript vanilla (`resources/views/layouts/app.blade.php`, `resources/js/app.js`); font sistem menjadi fallback. Tidak ada chart library, video, atau animasi berat baru.
- Dashboard impact meng-cache agregat 10 menit di `app/Services/SavingsService.php`; invalidasi saat workflow menyelesaikan dokumen dan saat admin memperbarui faktor/baseline.
- Query dashboard mengambil relasi tipe dengan eager loading. Daftar memakai pagination 15; indeks submission pada user/tanggal, status/tanggal-submit, dan tipe/status.
- `config/filesystems.php`: disk lokal privat tidak disajikan otomatis; controller mengotorisasi lampiran/arsip dan mengirim header `private, no-store`.
- Tema disimpan hanya sebagai preferensi UI `paperless-theme`; bukan klaim penghematan energi. Tidak ada data bisnis localStorage pada halaman SSR.
- Ukuran transfer, Web Vitals, Lighthouse, jumlah request, gzip, OPcache, dan performa belum diukur. Lihat `PERFORMANCE_TEST_PLAN.md`.
