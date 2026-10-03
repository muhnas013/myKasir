# 24 — DEFINITION_OF_DONE

Sebuah task/fitur selesai hanya bila SEMUA terpenuhi:
- [ ] Kriteria terima fitur di `docs/23_ACCEPTANCE_CRITERIA.md` terpenuhi, termasuk blok verifikasinya.
- [ ] Tes baru ditulis untuk perilaku baru (strategi: `docs/13_TESTING.md`) dan `php artisan test` hijau seluruhnya (tanpa regresi, tanpa tes di-skip).
- [ ] `./vendor/bin/pint --test` lulus.
- [ ] Struktur & penamaan sesuai `docs/12_PROJECT_STRUCTURE.md`; uang lewat `PriceCalculator`, stok lewat `StockService`.
- [ ] Tidak melanggar `docs/20_GUARDRAILS.md` dan `docs/21_SECURITY_RULES.md` (setiap aksi baru punya `authorize()` + tes 403).
- [ ] UI hanya memakai token & komponen `docs/26_UI_CONVENTIONS.md`; empat state ada.
- [ ] Migrasi baru punya `down()` yang bekerja.
- [ ] Bila fakta berubah (skema, aturan, peran, token): dokumen pemilik + `_MANIFEST.json` diperbarui dan `bash scripts/validate.sh` tanpa `[FAIL]`.
