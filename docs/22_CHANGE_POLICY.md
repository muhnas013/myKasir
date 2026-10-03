# 22 — CHANGE_POLICY

Prinsip: friksi sebanding irreversibilitas.

## Git
- Branch per fitur/bug: `feat/f3-complete-order`, `fix/void-stock-return`.
- Commit kecil, pesan Bahasa Indonesia atau Inggris konsisten, format `jenis: ringkasan` (`feat`, `fix`, `test`, `refactor`, `docs`, `chore`).
- Masuk `main` hanya lewat PR/merge oleh owner proyek setelah tes hijau + `pint --test` lulus. Dilarang commit langsung ke `main` dan `push --force`.

## Operasi irreversibel → wajib konfirmasi manusia eksplisit
| Operasi | Syarat sebelum jalan |
|---|---|
| Migrasi yang menghapus/mengganti nama kolom/tabel atau mengubah tipe | `down()` teruji, backup DB, persetujuan tertulis |
| Hapus data / koreksi data produksi | skrip terpisah + dry-run + backup + persetujuan |
| Reset/opname stok massal | backup + persetujuan owner |
| Rilis ke produksi | ikuti `docs/25_RELEASE_CHECKLIST.md` |
| Menambah/mengganti dependency | alasan + alternatif dipertimbangkan + persetujuan |
| Mengubah aturan hitung (06), skema (07), token UI (26) | perbarui dokumen pemilik + `_MANIFEST.json` lebih dulu |

## Reversibel (boleh tanpa gerbang)
Kode baru dalam scope, migrasi additive (tabel/kolom baru nullable), tes, dokumentasi.

## Rollback
- Kode: `git revert` commit rilis → deploy ulang.
- Migrasi: `php artisan migrate:rollback --step=1` HANYA bila `down()` aman; bila tidak, pulihkan dari backup sebelum rilis.
