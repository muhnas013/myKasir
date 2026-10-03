# 14 — ERROR_HANDLING

## Klasifikasi
| Kelas | Contoh | Ke pengguna | Ke log |
|---|---|---|---|
| Validasi | harga kosong, tunai kurang | pesan di bawah field (bahasa Indonesia, dari `lang/id/validation.php`) | tidak |
| Aturan bisnis | stok tidak cukup, shift belum dibuka, pesanan open saat tutup shift | toast peringatan dengan kalimat spesifik + langkah lanjut | `info` |
| Otorisasi | kasir membuka `/settings` | halaman 403 "Anda tidak punya akses ke halaman ini" | `warning` + user_id |
| PIN salah / terkunci | 5x salah | pesan di dialog PIN + sisa waktu kunci | `warning` + audit `auth.pin_locked` |
| Sistem | DB putus, exception tak terduga | toast bahaya "Terjadi kesalahan. Transaksi tidak tersimpan, coba lagi." + kode ref | `error` + stack trace + ref |

## Pola implementasi
- Aturan bisnis dilempar sebagai `App\Exceptions\BusinessRuleException` (pesan sudah ramah pengguna) dari Action; Livewire menangkap dan menampilkan toast.
- Exception lain ditangani handler global (`bootstrap/app.php` → `withExceptions`) yang menghasilkan `ref` (8 karakter acak) ditampilkan ke pengguna dan dicatat di log.
- Kegagalan di dalam `DB::transaction()` selalu me-rollback seluruh transaksi; layar kasir mempertahankan keranjang.

## Larangan
- Jangan tampilkan stack trace, SQL, atau path file ke pengguna (`APP_DEBUG=false` di produksi).
- Jangan menelan exception (`catch` kosong).
- Jangan menampilkan "berhasil" sebelum transaksi DB commit.
