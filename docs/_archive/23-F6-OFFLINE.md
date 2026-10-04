# Arsip — F6 OFFLINE (butir terbukti otomatis)

Diterima: dibuktikan hijau oleh `tests/Feature/Pos/OfflineSyncTest.php`. Proses & asumsi: `docs/06_BUSINESS_PROCESS.md` P7, `docs/02_SCOPE.md` §F6. Sisa butir manual (browser/printer) masih aktif di `docs/23_ACCEPTANCE_CRITERIA.md`.

- [x] Saat koneksi pulih, antrean terkirim ke endpoint sinkronisasi tanpa aksi manual kasir; tidak ada order duplikat walau dicoba kirim dua kali dengan `idempotency_key` yang sama (constraint unique). — `test_same_idempotency_key_is_not_duplicated`
- [x] Total tersimpan pasca-sinkron adalah hasil `PriceCalculator` server (lewat `CompleteOrder`, bukan logika baru); bila berbeda dari estimasi klien (`client_estimated_total`), `audit_logs` mencatat `action=order.offline_adjusted`. — `test_mismatched_client_estimate_is_flagged_in_audit_log`, `test_matching_client_estimate_is_not_flagged`
- [x] Potong stok (P5) terjadi di titik sinkron (lewat `CompleteOrder` → `StockService`), bukan di titik transaksi offline dibuat; stok tidak cukup saat sinkron membatalkan order itu (rollback), bukan silently diterima dengan stok negatif tak tercatat. — `test_sync_insufficient_stock_rolls_back_without_duplicate`
- [x] Sinkron terhadap shift yang sudah ditutup ditolak (422), tidak membuat order. — `test_sync_against_closed_shift_fails_without_creating_order`
- [x] Tamu/tanpa sesi tidak bisa memanggil endpoint sinkron. — `test_guest_cannot_sync`

Catatan: butir "stok boleh negatif sementara saat oversell antar-kasir offline" di rencana awal (02/06 asumsi) ternyata TIDAK demikian secara teknis — `StockService` yang sudah ada menolak (rollback) bila stok tak cukup di titik sinkron, sama seperti alur online. Oversell nyata hanya bisa terjadi bila dua order offline disinkron dan urutan sinkron pertama menghabiskan stok sebelum order kedua disinkron — order kedua akan ditolak 422 dan tersangkut di antrean lokal kasir (butuh penanganan manual, dicatat di landmine), bukan "diterima dengan stok negatif" seperti asumsi awal. Asumsi di 02/06 perlu dikoreksi pada sesi berikut.
