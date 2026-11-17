# Penyesuaian Laporan Kelompok

- Implementasi lokal adalah Laravel 13.42, bukan Laravel 11; jangan menyatakan downgrade/versi berbeda.
- DB `.env` saat ini SQLite. Jika laporan menyebut MySQL, ganti konfigurasi lokal dan jalankan migration normal tanpa reset.
- Tidak ada SSO, integrasi SIAKAD, tanda tangan elektronik tersertifikasi, atau hosting/VPS aktif.
- Dashboard menggabungkan aktivitas transaksi DB yang selesai dan panel proyeksi 3.000/tahun secara terpisah; data demo bukan capaian kampus.
- Kertas, perjalanan, faktor grid, transfer, dan konsumsi digital adalah asumsi yang perlu diberi sumber/periode/scope atau diukur di unit pilot.
- PDF internal simulasi tersedia, bukan tanda tangan elektronik tersertifikasi atau dokumen resmi kampus.
- Lighthouse, Web Vitals, byte transfer, request count, OPcache, dan target performa belum diukur; gunakan prosedur docs/PERFORMANCE_TEST_PLAN.md.
- Nilai target/hasil CO2e harus diperiksa ulang terhadap faktor primer dan observasi unit sebelum masuk Bab IV.

