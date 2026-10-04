# 05 — USER_ROLE

## Peran (`users.role`)
| Nilai | Label UI | Login |
|---|---|---|
| `owner` | Pemilik | email + kata sandi |
| `admin` | Admin | email + kata sandi (punya PIN untuk persetujuan) |
| `cashier` | Kasir | PIN 6 digit (pilih nama di layar login) |

## Matriks akses
| Kapabilitas (ability Policy) | owner | admin | cashier |
|---|---|---|---|
| `pos.transact` — buat & bayar pesanan | ✓ | ✓ | ✓ |
| `shift.manage-own` — buka/tutup shift sendiri | ✓ | ✓ | ✓ |
| `order.discount` — beri diskon | ✓ | ✓ | ✓ dengan PIN owner/admin |
| `order.void` — batalkan transaksi lunas | ✓ | ✓ | ✓ dengan PIN owner/admin |
| `menu.manage` — kelola kategori/produk/varian | ✓ | ✓ | ✗ |
| `stock.manage` — bahan, resep, stok masuk, opname | ✓ | ✓ | ✗ |
| `report.view-all` — laporan semua shift + export | ✓ | ✓ | ✗ |
| `report.view-own` — ringkasan shift sendiri | ✓ | ✓ | ✓ |
| `shift.force-close` — tutup shift kasir lain | ✓ | ✓ | ✗ |
| `settings.manage` — pengaturan outlet, pajak, bayar, struk, **penggajian** | ✓ | ✗ | ✗ |
| `user.manage` — tambah/ubah/nonaktifkan pengguna | ✓ | ✗ | ✗ |

"Dengan PIN" = kasir memicu aksi, lalu owner/admin memasukkan PIN mereka di dialog persetujuan. User yang menyetujui dicatat di `audit_logs`. Penegakan: `docs/21_SECURITY_RULES.md`.

## Aturan tambahan
- Minimal satu `owner` aktif harus selalu ada; owner terakhir tidak bisa dinonaktifkan atau diturunkan perannya.
- Pengguna dinonaktifkan (`is_active=false`), tidak pernah dihapus.
