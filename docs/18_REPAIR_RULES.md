# 18 — REPAIR_RULES

## Alur perbaikan
1. **Reproduksi**: tulis tes gagal (Feature/Unit) yang menunjukkan bug.
2. **Akar masalah**: telusuri sampai sebab pertama (pakai tabel gejala di 16); tulis satu kalimat sebabnya di pesan commit.
3. **Perbaikan minimal**: ubah sesedikit mungkin baris untuk membuat tes hijau.
4. **Regresi**: jalankan seluruh suite (`php artisan test`), bukan hanya tes baru.
5. **Data rusak**: bila bug sudah menulis data salah ke produksi (stok/kas), JANGAN perbaiki data lewat migrasi diam-diam — buat skrip koreksi terpisah, laporkan, minta persetujuan (22).

## Larangan
- Perbaikan tidak melebihi scope bug; refactor terpisah butuh izin.
- Dilarang menghijaukan tes dengan menghapus, men-skip, atau melonggarkan assertion.
- Dilarang mengubah skema (07) sebagai "perbaikan" tanpa melalui rute ubah skema.
- Dilarang mengubah aturan hitung (06) untuk mencocokkan angka yang salah.
