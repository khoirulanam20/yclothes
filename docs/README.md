# Dokumentasi [NAMA_BRAND] — Base Knowledge

> Pusat pengetahuan operasional website [NAMA_BRAND], toko fashion online dengan katalog lengkap (Pria, Wanita, Aksesoris, Sepatu, Tas, Muslimah), checkout multi-pembayaran, dan pengalaman belanja modern.

Dokumentasi ini dibuat untuk tim internal **dan** sebagai bahan dasar konten pelanggan. Gunakan placeholder berikut saat menyalin ke channel publik — nilainya diatur lewat **Admin → Konfigurasi**:

| Placeholder | Sumber di Admin |
|-------------|-----------------|
| `[NAMA_BRAND]` | Konfigurasi → Umum → Nama Brand |
| `[NOMOR_WA]` | Konfigurasi → Umum → Nomor WhatsApp |
| `[LOKASI_TOKO]` | Konfigurasi → Umum → Lokasi Toko |
| `[THRESHOLD_FREE_ONGKIR]` | Konfigurasi → Penjualan / Catalog Rules |
| `[URL_WEBSITE]` | Domain produksi toko |

---

## Siapa Membaca Apa?

| Peran | Mulai dari | Dokumen utama |
|-------|------------|---------------|
| **Pelanggan / End-user** | [Panduan Cara Belanja](pelanggan/cara-belanja.md) | `pelanggan/*` |
| **Customer Support** | [SOP Customer Support](internal/customer-support.md) | `internal/customer-support.md`, [FAQ](pelanggan/faq.md) |
| **Marketing** | [Brand Kit](internal/brand-kit.md) | `internal/brand-kit.md`, [Marketing Playbook](internal/marketing-playbook.md) |
| **Social Media** | [Konten Sosial Media](internal/konten-sosmed.md) | `internal/konten-sosmed.md`, `pelanggan/cara-belanja.md` |
| **Admin / Staff** | [Panduan Admin](internal/admin-guide.md) | `internal/admin-guide.md`, [Alur Pesanan](referensi/alur-pesanan.md) |
| **Semua tim** | [Fitur Website](referensi/fitur-website.md) | `referensi/*` |

---

## Struktur Folder

```
docs/
├── README.md                 ← Anda di sini (indeks)
├── pelanggan/                ← Siap dipublish ke website / WA / FAQ
│   ├── cara-belanja.md
│   ├── pembayaran.md
│   ├── pengiriman-retur.md
│   └── faq.md
├── internal/                 ← Hanya untuk tim internal
│   ├── brand-kit.md
│   ├── marketing-playbook.md
│   ├── konten-sosmed.md
│   ├── customer-support.md
│   └── admin-guide.md
└── referensi/                ← Single source of truth fitur & istilah
    ├── fitur-website.md
    ├── alur-pesanan.md
    └── glosarium.md
```

---

## Ringkasan Brand

**[NAMA_BRAND]** adalah destinasi fashion online yang menawarkan:

- Koleksi **fashion premium** multi-kategori dengan varian ukuran & warna
- **Flash sale** dengan countdown realtime di beranda
- **Promo & kupon** (diskon persen, nominal tetap, free ongkir)
- **Multi-pembayaran** Indonesia: transfer bank, QRIS, Midtrans, DOKU, KlikQRIS
- **Lacak pesanan** tanpa login + akun pelanggan lengkap (wishlist, alamat, retur)
- Pengiriman dari **[LOKASI_TOKO]** dengan estimasi 2–4 hari kerja

Halaman publik terkait: `/page/tentang-kami`, `/page/cara-belanja`, `/faq`

---

## Dokumen Terkait di Repository

| File | Untuk siapa | Isi |
|------|-------------|-----|
| [README.md](../README.md) | Developer | Install, deploy, tech stack |
| [yclothes-prd.md](../yclothes-prd.md) | Product/Engineering | PRD upgrade fitur |

---

## Cara Memperbarui Dokumentasi

1. Cek fitur terbaru di `routes/web.php` dan `resources/js/Pages/`
2. Update bagian `referensi/fitur-website.md` sebagai sumber utama
3. Sesuaikan FAQ dan template CS jika ada perubahan alur
4. Ganti placeholder `[NAMA_BRAND]` dll. sebelum publish ke pelanggan
