# 20 — GUARDRAILS

JANGAN:
1. Hard-code rahasia, kata sandi, PIN, atau URL produksi — semua dari `.env`.
2. Menonaktifkan validasi, CSRF, Policy, atau middleware peran "sementara".
3. Menghapus (hard delete) baris `orders`, `order_items`, `payments`, `shifts`, `stock_movements`, `audit_logs`.
4. Mengubah stok tanpa `StockService` atau menghitung uang di luar `PriceCalculator`.
5. Memakai `float`/`decimal` untuk uang.
6. Menambah dependency Composer/NPM tanpa alasan tertulis dan persetujuan (22).
7. Membangun fitur out-of-scope (02), termasuk PWA/offline sebelum F6.
8. Menjalankan `migrate:fresh`, `db:wipe`, atau seeder demo di produksi.
9. Commit langsung ke `main` atau push `--force`.
10. Menulis nilai warna/ukuran/font di luar tabel token 26.
11. Menampilkan pesan "berhasil" sebelum commit DB.

Aturan keamanan rinci: `docs/21_SECURITY_RULES.md`.
