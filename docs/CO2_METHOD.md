# Metodologi CO₂e

## Aktivitas aplikasi

Aktivitas dihitung dari submission berstatus `completed` di database, dikalikan baseline lembar dan perjalanan jenis layanan. Pengajuan draf, diajukan, revisi, dan ditolak tidak dihitung selesai. Nilai adalah estimasi counterfactual, bukan pembacaan listrik server.

## Skenario tahunan laporan

| Layanan | Pengajuan/bulan | Lembar/proses | Perjalanan/proses |
|---|---:|---:|---:|
| Aktif kuliah | 150 | 3 | 2 |
| Izin kegiatan | 40 | 10 | 4 |
| Peminjaman fasilitas | 60 | 4 | 3 |

Hasil volume: 3.000 pengajuan, 13.080 lembar bruto, 7.680 perjalanan per tahun. Dengan residual cetak 10%, kertas yang diasumsikan tidak digunakan adalah 11.772 lembar. Ini proyeksi kelompok, terpisah dari transaksi aplikasi.

## Rumus implementasi (`app/Services/EmissionCalculator.php`)

- `E_kertas = lembar × (4,99 / 1.000 kg) × 1,33932 kgCO₂e/kg`.
- `E_cetak = lembar × 0,001 kWh × 0,87 kgCO₂e/kWh`.
- `FE_motor = 2,2565 kgCO₂/L ÷ 45 km/L`; `E_transport = perjalanan × 30% × 2 km × FE_motor`.
- `E_before = E_kertas + E_cetak + E_transport`; `E_sisa = 10% × E_before`.
- MB desimal (`1 MB = 1.000.000 byte`), dikonversi ke GB desimal dengan pembagian 1.000. `D_GB = (kunjungan × MB halaman + submission × MB PDF)/1.000`.
- `E_digital = D_GB × (0,194 × 0,87 + 0,106 × 0,494)` kgCO₂e.
- `E_after = E_sisa + E_digital`; `net_avoided = E_before − E_after`; persen hanya jika baseline > 0.

Parameter operasional/embodied dan transfer 0,35 MB/kunjungan, 12 kunjungan/proses, PDF 0,15 MB adalah asumsi target, belum diukur. Faktor grid Jamali 0,87 merupakan proxy laporan ESDM 2019, bukan lokasi server yang dikonfirmasi. Faktor kertas bersumber pada faktor DESNZ 2024 sebagaimana disebut laporan; validasi publikasi dan batas proses sebelum hasil dipublikasikan. Skenario 3.000 volume tervalidasi unit-test dengan tolerance, bukan dikunci sebagai output.

## Biaya

Dashboard transaksi memakai baseline biaya Rp110/lembar + Rp200/halaman dari konfigurasi demo. Biaya bersih tahunan/VPS tidak dihitung sebagai aktual. Perjalanan dan biaya fisik memerlukan observasi unit.

## Batas

Tidak ada pengukuran energi server/perangkat. Ukuran transfer tidak diukur; target bukan hasil. Perjalanan hanya incremental sesuai asumsi model. Jangan menyebut angka sebagai audit, real-time, atau capaian kampus.
