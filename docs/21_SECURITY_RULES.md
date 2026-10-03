# 21 — SECURITY_RULES

## Autentikasi
- Owner/admin: email + kata sandi (bcrypt, minimal 8 karakter). Kasir: pilih nama + PIN 6 digit (bcrypt di `pin_hash`); PIN berurutan/berulang (`123456`, `111111`) ditolak, kecuali seeder `local`.
- `session()->regenerate()` setelah login; cookie `httponly`, `secure` (produksi), `same_site=lax`.
- Kunci layar: tombol "Kunci" kembali ke layar PIN tanpa mengakhiri shift.
- Rate limit: login 5x/menit per IP+email; PIN login dan PIN persetujuan 5x salah/15 menit per user (`RateLimiter`), lalu audit `auth.pin_locked`.

## Otorisasi
- Setiap rute dan aksi Livewire memanggil `authorize()`/`Gate` sesuai ability di `docs/05_USER_ROLE.md`; menyembunyikan tombol di UI BUKAN otorisasi.
- Persetujuan PIN: `App\Actions\Auth\VerifyApproverPin` memeriksa PIN milik user berperan owner/admin yang aktif, mengembalikan id penyetuju untuk dicatat.
- User nonaktif ditolak walau sesinya masih ada (middleware `EnsureUserIsActive`).

## Validasi input
- Semua input melalui FormRequest atau `rules()` Livewire; harga/qty integer ≥ 0; persen 0–100.
- Harga dan total TIDAK pernah diterima dari klien — server menghitung ulang dari `product_id` + opsi via `PriceCalculator`.
- Unggahan (logo, QRIS, foto produk): `image|mimes:jpg,png,webp|max:2048`, disimpan dengan nama acak.

## Data & rahasia
- Rahasia hanya di `.env`; `.env` tidak di-commit.
- `password`, `pin_hash` di `$hidden`; tidak pernah dikirim ke view atau log.
- Escaping output: Blade `{{ }}`; `{!! !!}` dilarang untuk data pengguna.
- Query lewat Eloquent/Query Builder; raw SQL wajib binding.
- Data transaksi & audit tidak dihapus (07); backup DB harian [TERBUKA: jadwal].

## Kepatuhan
Tidak ada regulasi khusus di luar praktik baik umum. [ASUMSI] Struk mencantumkan PB1 sesuai Perda pajak daerah setempat.
