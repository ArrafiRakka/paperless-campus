# Rencana Uji Performa

Status semua hasil: **BELUM DIUKUR**. Target laporan bukan capaian.

1. Jalankan aplikasi pada Laragon/Chrome desktop dan emulasi mobile 375 px.
2. Chrome DevTools Network: cache dinonaktifkan untuk first visit, lalu aktif untuk return visit; catat transferred bytes, resource count, document size, dan waktu.
3. Ukur halaman login, dashboard, form, detail/tracking, arsip, dampak. Jangan memasukkan download dokumen privat dalam cache publik.
4. Jalankan Lighthouse Performance, Accessibility, Best Practices pada URL lokal satu per satu. Simpan tanggal, versi Chrome/Lighthouse, device profile, jaringan, skor, FCP/LCP/TBT/CLS.
5. Ukur page transfer dari Network; target <=350 KB belum diverifikasi. Ukuran lampiran dan dokumen unduhan dilaporkan terpisah.
6. Untuk data transfer perjalanan sampel, dokumentasikan jumlah halaman, login/role, cache cold/warm, ukuran response, dan konversi MB desimal. Jangan gunakan Website Carbon untuk localhost sebagai hasil validasi.
