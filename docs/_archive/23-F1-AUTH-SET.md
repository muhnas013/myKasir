# 23 (arsip) — F1 — AUTH + SET

Diterima: 2026-10-04. Penegakan pindah ke test suite (`tests/Feature/Auth/*`, `tests/Feature/Settings/*`).

- [x] Given kasir aktif ber-PIN, When memilih nama + PIN benar, Then masuk ke layar Buka Shift/Kasir.
- [x] Given PIN salah 5x, Then dialog terkunci 15 menit dan baris `audit_logs.action=auth.pin_locked` tercipta.
- [x] Given cashier login, When membuka `/settings`, Then 403. (`/menu` belum ada — menyusul F2)
- [x] Given owner terakhir, When mencoba menonaktifkan dirinya, Then ditolak dengan pesan.
- [x] Given owner mengubah PB1 jadi 11%, Then pesanan baru memakai 11% dan perubahan tercatat `setting.updated`.

```
php artisan test --filter='Auth|Settings' → lulus: 16 passed, 0 failures
```
