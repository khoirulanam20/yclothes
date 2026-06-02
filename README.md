# 👕 yClothes — Fashion E-commerce

> Laravel 13 · Bootstrap 5 · Katalog Fashion Premium · Midtrans Payment · WhatsApp Checkout

Aplikasi toko online fashion dengan katalog produk, keranjang belanja, transaksi, manajemen ongkir, pembayaran Midtrans, dan checkout otomatis ke WhatsApp. **Fully responsive** — mobile, tablet, dan desktop.

---

## ✨ Fitur

### 🛍️ Frontend
- **Katalog produk** — grid responsif, filter kategori, search produk
- **Cart AJAX** — tambah/hapus/update qty tanpa reload
- **Varian produk** — pilih ukuran & warna via modal sebelum masuk keranjang
- **Flash sale countdown** — realtime, bisa diatur dari admin
- **Banner promo** — dinamis dari pengaturan admin
- **Checkout & transaksi** — form alamat, pilih pengiriman, Midtrans / bank transfer
- **Midtrans payment** — popup pembayaran (kartu kredit, VA, Alfamart, GoPay, dll.)
- **Bank Transfer** — checkout manual dengan konfirmasi via WhatsApp
- **Lacak pesanan** — cari pesanan via nomor pesanan + email, lihat status & timeline
- **Tentang Kami** — halaman statis dengan banner + konten (Trix editor) dari admin
- **Cara Belanja** — panduan belanja yang bisa diedit dari admin
- **Floating WhatsApp** — tombol WA fixed di semua halaman (kecuali checkout)

### 🔐 Admin Panel (`/admin`)
- **Produk** — CRUD dengan upload gambar, ukuran, warna (picker modal), harga diskon
- **Kategori** — CRUD dengan upload gambar
- **Pesanan** — daftar, detail, update status, konfirmasi pembayaran
- **Ongkos Kirim** — tarif per kota, weight-based (cost per kg)
- **Payment Bank** — kelola rekening untuk transfer manual
- **Halaman Tentang Kami** — upload banner + Trix editor konten
- **Halaman Cara Belanja** — upload banner + Trix editor konten
- **Pengaturan Toko** — brand, logo, WA, warna gold & accent, sosial media, flash sale, lokasi toko
- **Tampilan Toko** — SEO meta, hero section, CTA, banner promo

### ⚙️ Teknis
- **Midtrans Snap** — popup pembayaran + webhook server-to-server via `overrideNotifUrl`
- **Ongkir weight-based** — `base_cost + ceil(kg - 1) × cost_per_kg`
- **Tampilan dinamis** — hero, banner, warna, SEO meta semua dari database
- **No build tools** — Bootstrap statis di `public/bootstrap/`, tanpa Vite/Webpack/npm
- **Database driver** — session, cache, dan queue pakai database

---


## 🚀 Cara Install
> Pastikan database MySQL `yclothes` sudah dibuat. Migration akan membuat tabel-tabelnya otomatis.

```bash
# 1. Setup otomatis (install, .env, key, migrate)
composer run setup

# 2. Seed data awal (admin + contoh produk + ongkos kirim)
php artisan migrate:fresh --seed

# 3. Storage link (buat akses gambar produk)
php artisan storage:link

# 4. Jalankan
php artisan serve
```

Akses di **`http://localhost:8000`**.

### Reset Data

```bash
php artisan migrate:fresh --seed
```

### Midtrans (Opsional)

Isi di `.env` untuk mengaktifkan pembayaran Midtrans:

```env
MIDTRANS_ACTIVE=true
MIDTRANS_MERCHANT_ID=your_merchant_id
MIDTRANS_SERVER_KEY=Mid-server-xxx
MIDTRANS_CLIENT_KEY=Mid-client-xxx
MIDTRANS_IS_PRODUCTION=false   # true untuk production
```

Webhook notifikasi dikirim otomatis via `Config::$overrideNotifUrl` — tidak perlu setup di dashboard Midtrans.

---

## 🚀 Deploy ke Shared Hosting

### ✅ Sudah siap — gak perlu diotak-atik

| Item | Keterangan |
|------|------------|
| Frontend assets | Bootstrap CSS/JS statis di `public/bootstrap/` — langsung jalan |
| Build tools | Tidak ada Vite/Webpack/npm — skip semua |
| Session & cache | Pakai driver `database` — butuh tabel (migration sudah sediakan) |
| Warna & tampilan | Diatur dari admin panel, simpan di DB |
| Gambar produk | Upload via admin, simpan di `storage/app/public/` |

### ⚠️ Yang perlu dicek sebelum deploy

| Cek | Keterangan |
|-----|------------|
| **PHP ≥ 8.3** | Laravel 13 wajib PHP 8.3+. Cek via cPanel → Select PHP Version |
| **MySQL database** | Buat database kosong via phpMyAdmin. Catat nama, user, password |
| **Ekstensi PHP** | Pastikan `BCMath`, `Ctype`, `Fileinfo`, `JSON`, `Mbstring`, `OpenSSL`, `PDO`, `Tokenizer`, `XML`, `GD` aktif |
| **Composer** | Hosting harus ada akses Composer (via SSH atau terminal) |

### 1. Upload file

Upload seluruh folder project via FTP/File Manager ke folder `public_html/yclothes/` (atau nama bebas), kecuali:

| Jangan upload | Alasan |
|---------------|--------|
| `.env` | Nanti buat ulang di server |
| `storage/` | Kosongin dulu, nanti regenerasi |
| `vendor/` | Nanti `composer install` di server |
| `node_modules/` | Gak ada |
| `.git/` | Gak perlu |

**Contoh struktur setelah upload (via FileZilla / cPanel File Manager):**
```
public_html/
└── yclothes/
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/
    │   ├── bootstrap/
    │   ├── css/
    │   ├── js/
    │   └── index.php
    ├── resources/
    ├── routes/
    ├── composer.json
    ├── composer.lock
    ├── .env.example
    └── artisan
```

> File `.env`, folder `storage/`, dan folder `vendor/` **tidak** perlu diupload — nanti dibuat otomatis di server via command setup.

### 2. Setup di server

```bash
# Masuk SSH atau terminal hosting
cd public_html/yclothes

# Setup otomatis (install, .env, key, migrate)
composer run setup

# (Opsional) Seed data awal — admin + contoh produk
php artisan migrate --seed

# Storage link
php artisan storage:link

# Cache
php artisan config:cache
php artisan route:cache
```

> **Catatan:** Sebelum `composer run setup`, pastikan `.env` sudah diisi `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL=https://domainkamu.com`.  
> Atau edit setelah file `.env` tercopy otomatis.

### 3. Arahkan Document Root

Document root adalah folder yang "dilihat" pengunjung saat buka domain kamu.
Biasanya hosting otomatis mengarah ke folder `public_html` atau `htdocs`.

Karena project ini pakai Laravel, document root harus diarahkan ke folder **`public/`** di dalam project — bukan folder utama project.

**Caranya:**

| Cara | Langkah |
|------|---------|
| **cPanel** | Buka **Domains** → pilih domain → ganti **Document Root** menjadi `public_html/nama-folder-project/public` |
| **File Manager** | Upload semua file ke `public_html/yclothes/` lalu arahkan document root ke `public_html/yclothes/public` |
| **Manual** | Buat file `.htaccess` di `public_html` yang isinya: `RewriteRule ^(.*)$ yclothes/public/$1 [L]` |

**Contoh struktur di server:**
```
public_html/           ← folder utama hosting (jangan dipake untuk file project)
└── yclothes/          ← folder project
    ├── app/
    ├── bootstrap/
    ├── public/         ← ini yang harus jadi document root
    ├── vendor/
    └── .env
```

> 💡 **Tips:** Kalau bingung, tanya ke support hosting: "Tolong arahkan document root domain saya ke folder `public` di dalam folder project Laravel."

### 4. Permission

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 755 public/storage
```

---

## 🔑 Admin Panel

| URL | Email | Password |
|-----|-------|----------|
| `/admin` | `admin@yclothes.test` | `admin123` |

---

## 📱 Responsivitas

| Breakpoint | Grid Produk | Filter | Cart | Admin Sidebar |
|------------|-------------|--------|------|---------------|
| **Mobile** (< 768px) | 2 kolom → 1 kolom | Offcanvas | Card list | Offcanvas |
| **Tablet** (768–992px) | 2 kolom | Offcanvas | Card list | Offcanvas |
| **Desktop** (> 992px) | 3 kolom | Sidebar tetap | Table | Sidebar tetap |

Semua halaman diuji di Chrome DevTools (320px–1440px).

---

## 🧪 Testing

In-memory SQLite — tanpa database eksternal. **54 tests — semuanya passing.**

```bash
composer run test
```

**Cakupan test:**
| Area | Tes |
|------|-----|
| Homepage | Homepage bisa diakses |
| Produk | Listing, kategori, search, detail, 404, sorting |
| Cart | Tambah, update qty, hapus, checkout, cart kosong |
| Order | Buat pesanan, lacak via nomor WA, Midtrans payment finish |
| Admin Auth | Login sukses/gagal, middleware redirect |
| Admin Produk | List, create, store, edit, update, delete |
| Admin Pengaturan | Baca & simpan settings, XSS stripped |
| Admin Tampilan | Baca & simpan appearance, auth required |
| Ongkos Kirim | Weight-based shipping cost calculation |
| Shipping Costs CRUD | List, create, edit, update, delete |

```bash
# Format kode
./vendor/bin/pint
```

---

## 🏗️ Struktur Penting

```
├── routes/web.php                                    # Semua routes
├── resources/views/
│   ├── layouts/app.blade.php                         # Frontend layout (header, navbar, footer, OG tags, variant modal)
│   ├── layouts/admin/                                # Admin layout + partials
│   ├── home/index.blade.php                          # Halaman depan (hero, flash sale, kategori, produk)
│   ├── products/                                     # Katalog + detail + card partial
│   ├── cart/index.blade.php                          # Keranjang AJAX
│   ├── order/                                        # Success, detail, lacak, midtrans pay page
│   ├── cara-belanja/                                  # Halaman cara belanja (Trix editor)
│   └── admin/                                        # CRUD produk, kategori, ongkir, payment bank, appearance, settings
├── public/
│   ├── css/custom.css                                # Custom styling
│   ├── js/cart.js                                    # Cart AJAX + variant modal
│   └── js/countdown.js                               # Countdown flash sale
├── app/
│   ├── Http/Controllers/
│   │   ├── CheckoutController.php                    # Checkout + Midtrans payment finish
│   │   ├── MidtransController.php                    # Webhook notification handler
│   │   ├── OrderController.php                       # Order tracking, detail
│   │   └── ...
│   ├── Services/MidtransService.php                  # Snap token, verify payment, override notif URL
│   └── helpers.php                                   # Helper setting()
├── config/midtrans.php                               # Konfig Midtrans env wrapper
├── storage/app/public/
│   ├── products/                                     # Upload gambar produk
│   └── categories/                                   # Upload gambar kategori
```

---

## 🔒 Keamanan (Production)

Saat deploy ke production, pastikan variabel berikut di `.env`:

| Variabel | Nilai disarankan |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_SECURE_COOKIE` | `true` (wajib jika HTTPS) |
| Kredensial admin | Ganti default seeder (`admin123`) |

Fitur keamanan yang sudah diterapkan:

- Token akses per pesanan (URL publik membutuhkan `?token=...`)
- Lacak pesanan: nomor pesanan + email
- Pembayaran Midtrans: status hanya dari verifikasi server / webhook
- Sanitasi HTML konten Trix (About / Cara Belanja)
- Middleware admin (`is_admin`), rate limiting, security headers
- CSRF pada form & AJAX (kecuali webhook Midtrans)

---

## ⚙️ Konfigurasi

### Warna & Brand (Admin → Pengaturan)

| Setting | Default | Keterangan |
|---------|---------|------------|
| Warna Gold | `#C2A56D` | CTA, badge, harga premium |
| Warna Accent | `#547A95` | Tombol sekunder, link aktif |
| Brand Name | yClothes | Tampil di navbar & footer |
| Brand Logo | — | Upload logo toko |
| WA Number | 6280000000000 | Tujuan checkout WhatsApp |
| Flash Sale End | End of today | Countdown flash sale |

### Tampilan Toko (Admin → Tampilan Toko)

| Setting | Default | Keterangan |
|---------|---------|------------|
| Site Title | yClothes | Judul tab browser & OG title |
| Site Description | Toko fashion premium... | Meta description & OG description |
| Hero Title | Koleksi Terbaru<br>Musim Ini | Teks utama hero banner (HTML ok) |
| Hero Subtitle | Temukan gaya terbaikmu... | Teks pendukung hero |
| Hero Image | Unsplash default | Gambar banner utama (upload) |
| CTA Text | Shop Now → | Teks tombol hero |
| CTA Link | /products | Tujuan tombol hero |
| Banner Title | Free Ongkir > Rp 200rb | Top bar promo |

---

## 🛠️ Tech Stack

- **Backend:** Laravel 13, PHP 8.3, MySQL
- **Frontend:** Bootstrap 5, CSS Variables, Google Fonts (Playfair Display + DM Sans)
- **Assets:** Bootstrap static files (`public/bootstrap/`) — **no Vite/npm**
- **Cart:** Session-based
- **Payment:** Midtrans Snap (popup) + Bank Transfer manual
- **Shipping:** Weight-based (cost per kg per kota)
- **Images:** Upload ke `storage/app/public/{products,categories}/` — akses via `$model->image_url`
- **Session/Cache/Queue:** Database driver

---

## 📄 Lisensi

[MIT](https://opensource.org/licenses/MIT)
