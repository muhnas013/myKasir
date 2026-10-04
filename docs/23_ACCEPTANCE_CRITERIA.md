# 23 — ACCEPTANCE_CRITERIA

Hanya fitur AKTIF. Fitur yang diterima dipindah ke `docs/_archive/23-{kode}.md`. Fitur: `docs/01_PRD.md`; proses & rumus: `docs/06_BUSINESS_PROCESS.md`; peran: `docs/05_USER_ROLE.md`; DoD: `docs/24_DEFINITION_OF_DONE.md`.
Data uji standar: Es Kopi Susu Gula Aren Rp 18.000, Nasi Goreng Spesial Rp 25.000, Pisang Goreng Rp 12.000.

## F3 — POS + PAY + SHIFT + VOID (sisa: uji manual)
Butir otomatis sudah diterima dan diarsipkan di `docs/_archive/23-F3-POS-PAY-SHIFT-VOID.md`. Sisa butir manual:
- [ ] Struk tercetak penuh di kertas 58 mm pada printer thermal nyata (nama outlet, nomor, waktu WITA, item, subtotal, PB1, total, bayar, kembali, footer) (manual).
- [ ] Alur 3 item tunai selesai ≤ 30 detik oleh penguji yang belum pernah memakai (manual, stopwatch).

## Verifikasi UI (semua fase)
- [ ] `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil (warna hanya dari `resources/css/app.css`).
- [ ] Tiap halaman ber-data punya empat state (26).
