# Task Management & Sprint Planning — YClothes E-Commerce Upgrade

**Dokumen**: Task Management & Sprint Planning
**Versi**: 1.0
**Tanggal**: 2 Juni 2026
**Metodologi**: Agile Scrum
**Sprint Duration**: 2 minggu
**Total Sprints**: 20 sprint (40 minggu)
**MVP Target**: 9 sprint (18 minggu) untuk P0 modul

---

## 1. Overview

### Project Summary
Upgrade YClothes dari toko online sederhana menjadi e-commerce platform setara Bagisto. Menambahkan 10+ modul baru: Customer Account, EAV Attributes, Configurable Products, Inventory, Tax, Promotions, CMS, ACL, REST API, SEO, Reporting.

### Key Metrics
- **Total Modul Baru**: 12 modul (P0: 7, P1: 4, P2: 1)
- **Total Requirements**: 80+ REQ
- **Total Sprint**: 20 sprint
- **Target MVP**: 9 sprint (18 minggu) untuk P0
- **Team Size**: 1 full-stack developer (Khoirul)

### Tech Stack
- **Backend**: Laravel 13, PHP 8.3
- **Frontend**: Blade + Bootstrap 5
- **Database**: MySQL
- **Payment**: Midtrans (existing)
- **Queue**: Redis / Database

---

## 2. Sprint Timeline

| Sprint | Periode | Focus Area | Status |
|--------|---------|------------|--------|
| Sprint 1 | 02-13 Jun 2026 | Customer Auth & Profile | Planned |
| Sprint 2 | 16-27 Jun 2026 | Address Book, Wishlist, Reviews | Planned |
| Sprint 3 | 30 Jun - 11 Jul 2026 | EAV Attribute System | Planned |
| Sprint 4 | 14-25 Jul 2026 | Configurable Products | Planned |
| Sprint 5 | 28 Jul - 08 Agu 2026 | Inventory & Warehouse | Planned |
| Sprint 6 | 11-22 Agu 2026 | Stock Movements & Alerts | Planned |
| Sprint 7 | 25 Agu - 05 Sep 2026 | Tax Management | Planned |
| Sprint 8 | 08-19 Sep 2026 | Cart Rules & Coupons | Planned |
| Sprint 9 | 22 Sep - 03 Okt 2026 | Catalog Rules & Free Shipping | Planned |
| **MVP** | **03 Okt 2026** | **P0 Complete** | **Target** |
| Sprint 10 | 06-17 Okt 2026 | CMS: Pages | Planned |
| Sprint 11 | 20-31 Okt 2026 | CMS: Blog, Slider, Navigation | Planned |
| Sprint 12 | 03-14 Nov 2026 | Admin ACL & Activity Log | Planned |
| Sprint 13 | 17-28 Nov 2026 | REST API: Product & Customer | Planned |
| Sprint 14 | 01-12 Des 2026 | REST API: Cart & Checkout | Planned |
| Sprint 15 | 15-26 Des 2026 | SEO: Sitemap, OG, Schema | Planned |
| Sprint 16 | 29 Des - 09 Jan 2027 | Multi-channel Setup | Planned |
| Sprint 17 | 12-23 Jan 2027 | Reporting: Sales & Revenue | Planned |
| Sprint 18 | 26 Jan - 06 Feb 2027 | Reporting: Product & Customer | Planned |
| Sprint 19 | 09-20 Feb 2027 | Email Templates System | Planned |
| Sprint 20 | 23 Feb - 06 Mar 2027 | Testing, Docs & Launch | Planned |

---

## 3. Sprint Breakdown

### Sprint 1: Customer Auth & Profile
**Periode**: 02-13 Jun 2026 (2 minggu)
**Sprint Goal**: Implementasi sistem customer registration, login, dan profile.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-001-01 | Membuat migration tabel customers | REQ-CUST-001 | 3 | Todo |
| US-001-02 | Customer registration form (name, email, phone, password) | REQ-CUST-001 | 5 | Todo |
| US-001-03 | Customer login dengan email + password | REQ-CUST-001 | 3 | Todo |
| US-001-04 | Customer profile page (name, email, phone, avatar) | REQ-CUST-002 | 5 | Todo |
| US-001-05 | Forgot password + reset password | REQ-CUST-008 | 5 | Todo |
| US-001-06 | Email verification setelah register | REQ-CUST-009 | 5 | Todo |
| US-001-07 | Checkout: guest boleh lanjut, customer login bisa pilih alamat | REQ-CUST-007 | 5 | Todo |
| US-001-08 | Update existing orders: tambah customer_id | — | 3 | Todo |

**Deliverables**:
- [ ] Migration + Model Customer
- [ ] Register + Login page
- [ ] Customer profile (edit)
- [ ] Forgot password flow
- [ ] Email verification
- [ ] Cart persistence: keranjang tetap setelah login/logout

**Acceptance Criteria**:
- [ ] Customer bisa register dengan email valid
- [ ] Customer bisa login dan logout
- [ ] Profile bisa diedit (nama, email, avatar)
- [ ] Forgot password mengirim email reset
- [ ] Checkout bisa pilih login atau guest

---

### Sprint 2: Address Book, Wishlist, Reviews
**Periode**: 16-27 Jun 2026 (2 minggu)
**Sprint Goal**: Implementasi address book, wishlist, dan product reviews.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-002-01 | Migration + model customer_addresses | REQ-CUST-003 | 3 | Todo |
| US-002-02 | CRUD address book (multiple alamat, set default) | REQ-CUST-003 | 5 | Todo |
| US-002-03 | Alamat shipping & billing terpisah | REQ-CUST-003 | 3 | Todo |
| US-002-04 | Migration + model wishlists | REQ-CUST-005 | 2 | Todo |
| US-002-05 | Wishlist toggle di product detail + halaman wishlist | REQ-CUST-005 | 5 | Todo |
| US-002-06 | Migration + model reviews | REQ-CUST-006 | 3 | Todo |
| US-002-07 | Write review form setelah order completed | REQ-CUST-006 | 5 | Todo |
| US-002-08 | Rating display di product card + detail (average, count) | REQ-PROD-006 | 5 | Todo |
| US-002-09 | Verified purchase badge di review | REQ-PROD-006 | 3 | Todo |
| US-002-10 | Admin approve/reject review | REQ-CUST-006 | 3 | Todo |

**Deliverables**:
- [ ] Address book (CRUD, multi-alamat, default)
- [ ] Wishlist (toggle, list page)
- [ ] Reviews (write, display, approve workflow)
- [ ] Rating average otomatis di product card

**Acceptance Criteria**:
- [ ] Customer bisa tambah/edit/hapus alamat
- [ ] Checkout bisa pilih alamat dari address book
- [ ] Wishlist toggle di product detail
- [ ] Customer bisa tulis review setelah order completed
- [ ] Admin bisa approve/reject review

---

### Sprint 3: EAV Attribute System
**Periode**: 30 Jun - 11 Jul 2026 (2 minggu)
**Sprint Goal**: Implementasi Entity-Attribute-Value system untuk product attributes dinamis.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-003-01 | Migration: attributes, attribute_options, product_attribute_values | REQ-PROD-001 | 5 | Todo |
| US-003-02 | CRUD attribute families | REQ-PROD-001 | 5 | Todo |
| US-003-03 | CRUD attributes (type: text, select, multiselect, boolean, price) | REQ-PROD-001 | 8 | Todo |
| US-003-04 | CRUD attribute options (untuk select/multiselect) | REQ-PROD-001 | 5 | Todo |
| US-003-05 | Assign attribute family ke product (saat create/edit) | REQ-PROD-001 | 5 | Todo |
| US-003-06 | Product form dinamis: field berubah sesuai attribute family | REQ-PROD-001 | 8 | Todo |
| US-003-07 | Display attribute values di product detail page | REQ-PROD-001 | 5 | Todo |
| US-003-08 | Filter produk by attribute di frontend | REQ-PROD-001 | 5 | Todo |
| US-003-09 | Migrate existing sizes/colors ke EAV system | REQ-PROD-001 | 5 | Todo |

**Deliverables**:
- [ ] 3 migration baru (attributes, options, values)
- [ ] Admin CRUD attribute families & attributes
- [ ] Product form dinamis berdasarkan family
- [ ] Frontend filter by attribute
- [ ] Migrasi data sizes/colors lama ke EAV

**Acceptance Criteria**:
- [ ] Admin bisa buat attribute custom (Ukuran, Bahan, Merk, dll)
- [ ] Product form menyesuaikan attribute family
- [ ] Attribute value tersimpan dan tampil di detail
- [ ] Filter produk by attribute berfungsi

---

### Sprint 4: Configurable Products
**Periode**: 14-25 Jul 2026 (2 minggu)
**Sprint Goal**: Implementasi configurable product dengan multiple variants.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-004-01 | Migration: product_variants | REQ-PROD-002 | 3 | Todo |
| US-004-02 | Admin: buat configurable product dengan parent-child variants | REQ-PROD-002 | 8 | Todo |
| US-004-03 | Admin: manage variants (SKU, stock, price override, image per variant) | REQ-PROD-002 | 8 | Todo |
| US-004-04 | Frontend: variant selector (ukuran + warna pilih, gambar berubah) | REQ-PROD-002 | 8 | Todo |
| US-004-05 | Frontend: stock per variant indicator | REQ-PROD-002 | 3 | Todo |
| US-004-06 | Cart: simpan variant yang dipilih | REQ-PROD-002 | 5 | Todo |
| US-004-07 | Order: variant info di order item (size, color, SKU) | REQ-PROD-002 | 3 | Todo |
| US-004-08 | Up-selling: cross-sell, up-sell, related products | REQ-PROD-007 | 5 | Todo |

**Deliverables**:
- [ ] Configurable product admin (parent + variants)
- [ ] Variant selector UI (size + color, dynamic image)
- [ ] Cart & order menampilkan variant
- [ ] Cross-sell / related products

**Acceptance Criteria**:
- [ ] Admin bisa buat product dengan multiple variants
- [ ] Setiap variant punya SKU, stock, price sendiri
- [ ] Customer bisa pilih variant di product detail
- [ ] Variant info tersimpan di order
- [ ] Cross-sell & related product tampil

---

### Sprint 5: Inventory & Warehouse
**Periode**: 28 Jul - 08 Agu 2026 (2 minggu)
**Sprint Goal**: Implementasi inventory management dengan multi-warehouse.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-005-01 | Migration: inventories, warehouses | REQ-INV-001, REQ-INV-003 | 3 | Todo |
| US-005-02 | CRUD warehouses | REQ-INV-003 | 5 | Todo |
| US-005-03 | Stock management per product (qty, track stock toggle) | REQ-INV-001 | 5 | Todo |
| US-005-04 | Stock per variant (untuk configurable product) | REQ-INV-002 | 5 | Todo |
| US-005-05 | Admin panel: stock list per warehouse | REQ-INV-003 | 5 | Todo |
| US-005-06 | Frontend: stock indicator (tersedia / habis / sisa X) | REQ-INV-001 | 3 | Todo |
| US-005-07 | Kurangi stock otomatis saat order status = paid | REQ-INV-001 | 5 | Todo |
| US-005-08 | Product tidak bisa di-order jika stock = 0 (kecuali backorder) | REQ-INV-001 | 3 | Todo |

**Deliverables**:
- [ ] Multi-warehouse CRUD
- [ ] Stock management per product & variant
- [ ] Auto-decrement stock saat order paid
- [ ] Stock indicator di frontend

**Acceptance Criteria**:
- [ ] Admin bisa buat warehouse (pusat, cabang)
- [ ] Stock bisa di-set per product & per variant
- [ ] Stock berkurang otomatis ketika order dibayar
- [ ] Product habis tidak bisa di-order
- [ ] Stock indicator muncul di product detail

---

### Sprint 6: Stock Movements & Alerts
**Periode**: 11-22 Agu 2026 (2 minggu)
**Sprint Goal**: Implementasi stock movement log, low stock alert, backorder.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-006-01 | Migration: stock_movements | REQ-INV-004 | 3 | Todo |
| US-006-02 | Stock movement log: barang masuk, keluar, transfer, adjustment | REQ-INV-004 | 8 | Todo |
| US-006-03 | Stock movement history per product | REQ-INV-004 | 3 | Todo |
| US-006-04 | Stock adjustment form (admin koreksi stock dengan reason) | REQ-INV-007 | 5 | Todo |
| US-006-05 | Low stock threshold setting + alert via dashboard | REQ-INV-005 | 5 | Todo |
| US-006-06 | Backorder: allow checkout with note "estimasi X hari" | REQ-INV-006 | 5 | Todo |
| US-006-07 | Stock transfer antar warehouse | REQ-INV-004 | 5 | Todo |

**Deliverables**:
- [ ] Stock movement log (in, out, transfer, adjustment)
- [ ] Stock adjustment form
- [ ] Low stock alert (notifikasi admin)
- [ ] Backorder feature
- [ ] Stock transfer antar warehouse

**Acceptance Criteria**:
- [ ] Semua perubahan stock tercatat di log
- [ ] Admin bisa koreksi stock dengan reason
- [ ] Notifikasi muncul ketika stock dibawah threshold
- [ ] Backorder bisa diaktifkan/dinonaktifkan per product

---

### Sprint 7: Tax Management
**Periode**: 25 Agu - 05 Sep 2026 (2 minggu)
**Sprint Goal**: Implementasi multi-tax rates, tax zones, PPN Indonesia.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-007-01 | Migration: tax_rates, tax_rate_categories | REQ-TAX-001 | 3 | Todo |
| US-007-02 | CRUD tax rates (percentage, nama, aktif/nonaktif) | REQ-TAX-001 | 5 | Todo |
| US-007-03 | Tax zones: assign tax rate per provinsi/kota | REQ-TAX-002 | 5 | Todo |
| US-007-04 | Tax groups: assign tax rate per kategori produk | REQ-TAX-004 | 5 | Todo |
| US-007-05 | Tax included/excluded setting | REQ-TAX-003 | 3 | Todo |
| US-007-06 | Tax calculation otomatis di cart + checkout | REQ-TAX-005 | 5 | Todo |
| US-007-07 | Tax display: subtotal, tax, grand total | REQ-TAX-005 | 3 | Todo |
| US-007-08 | Seed default: PPN 11% untuk Indonesia | REQ-TAX-004 | 2 | Todo |

**Deliverables**:
- [ ] Tax rates CRUD
- [ ] Tax zones (by provinsi/kota)
- [ ] Tax groups (by kategori produk)
- [ ] Tax calculation di cart & checkout
- [ ] Seed PPN 11%

**Acceptance Criteria**:
- [ ] Admin bisa buat tax rate baru
- [ ] Tax zones berfungsi (provinsi A beda pajak dengan B)
- [ ] Tax calculation otomatis di subtotal
- [ ] Cart & checkout menampilkan breakdown pajak

---

### Sprint 8: Cart Rules & Coupons
**Periode**: 08-19 Sep 2026 (2 minggu)
**Sprint Goal**: Implementasi cart rules, coupon codes, discount logic.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-008-01 | Migration: cart_rules | REQ-PROMO-001 | 3 | Todo |
| US-008-02 | CRUD cart rules (name, type, amount, min order, period) | REQ-PROMO-001 | 8 | Todo |
| US-008-03 | Discount type: percentage, fixed amount | REQ-PROMO-001 | 5 | Todo |
| US-008-04 | Coupon codes: unique code, usage limit, per customer limit | REQ-PROMO-003 | 5 | Todo |
| US-008-05 | Discount calculation di cart + checkout | REQ-PROMO-001 | 5 | Todo |
| US-008-06 | Rule conditions: min order amount, specific categories | REQ-PROMO-001 | 8 | Todo |
| US-008-07 | Date range: start_date, end_date validation | REQ-PROMO-001 | 3 | Todo |
| US-008-08 | Coupon input field di cart page | REQ-PROMO-003 | 3 | Todo |

**Deliverables**:
- [ ] Cart rules CRUD
- [ ] Coupon code generation
- [ ] Discount calculation engine
- [ ] Coupon input + apply di cart
- [ ] Rule conditions (min order, kategori, periode)

**Acceptance Criteria**:
- [ ] Admin bisa buat cart rule dengan coupon code
- [ ] Discount terhitung otomatis di cart
- [ ] Coupon bisa diinput dan divalidasi
- [ ] Rule conditions berfungsi (min order, kategori tertentu)

---

### Sprint 9: Catalog Rules & Free Shipping
**Periode**: 22 Sep - 03 Okt 2026 (2 minggu)
**Sprint Goal**: Implementasi catalog rules, free shipping promotion.

#### User Stories

| Story ID | User Story | REQ ID | SP | Status |
|----------|------------|--------|----|--------|
| US-009-01 | Catalog rules: diskon langsung ke harga produk | REQ-PROMO-002 | 8 | Todo |
| US-009-02 | Display discounted price di product card + detail | REQ-PROMO-002 | 5 | Todo |
| US-009-03 | Free shipping: min order amount threshold | REQ-PROMO-004 | 5 | Todo |
| US-009-04 | Free shipping progress bar di cart ("Belanja Rp X lagi gratis ongkir") | REQ-PROMO-004 | 5 | Todo |
| US-009-05 | Buy X Get Y: logic + admin setup | REQ-PROMO-005 | 8 | Todo |
| US-009-06 | Tiered pricing: quantity discount (beli 3+ diskon 10%) | REQ-PROMO-006 | 8 | Todo |
| US-009-07 | Test & fix: semua promo rules tidak konflik | — | 3 | Todo |

**Deliverables**:
- [ ] Catalog rules engine
- [ ] Free shipping promotion
- [ ] Buy X Get Y logic
- [ ] Tiered pricing
- [ ] Free shipping progress bar

**Acceptance Criteria**:
- [ ] Catalog rule menampilkan harga diskon langsung
- [ ] Free shipping trigger pada min order
- [ ] Buy X Get Y logic berfungsi
- [ ] Tiered pricing otomatis
- [ ] Tidak ada konflik antar rule

---

### MVP Target — 03 Oktober 2026

**9 Sprint (18 minggu) — P0 Modul Complete**

---

### Sprint 10: CMS Pages
**Periode**: 06-17 Okt 2026 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-010-01 | Migration: cms_pages | 2 | Todo |
| US-010-02 | CRUD pages dengan WYSIWYG editor (Trix / TinyMCE) | 8 | Todo |
| US-010-03 | Page slug, status publish/draft | 3 | Todo |
| US-010-04 | Meta title + description per page | 3 | Todo |
| US-010-05 | Dynamic route: /page/{slug} | 3 | Todo |
| US-010-06 | Migrasi halaman About + Cara Belanja ke CMS | 3 | Todo |

---

### Sprint 11: CMS Blog, Slider, Navigation
**Periode**: 20-31 Okt 2026 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-011-01 | Migration: blog_posts | 2 | Todo |
| US-011-02 | CRUD blog dengan featured image, excerpt | 5 | Todo |
| US-011-03 | Blog list + detail page | 5 | Todo |
| US-011-04 | Image slider: admin CRUD, tampil di homepage | 5 | Todo |
| US-011-05 | Navigation builder: multi-level menu (header, footer) | 8 | Todo |
| US-011-06 | FAQ: kategori, question, answer, accordion | 5 | Todo |

---

### Sprint 12: Admin ACL & Activity Log
**Periode**: 03-14 Nov 2026 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-012-01 | Migration: admin_roles | 2 | Todo |
| US-012-02 | CRUD roles dengan permission matrix | 8 | Todo |
| US-012-03 | Assign role ke admin user | 3 | Todo |
| US-012-04 | Permission check middleware | 5 | Todo |
| US-012-05 | Activity log: catat semua aksi admin | 5 | Todo |
| US-012-06 | Activity log viewer di admin panel | 3 | Todo |

---

### Sprint 13: REST API — Product & Customer
**Periode**: 17-28 Nov 2026 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-013-01 | Install Sanctum + setup API auth | 3 | Todo |
| US-013-02 | Product API: GET list, GET detail, search, filter | 8 | Todo |
| US-013-03 | Category API: list products per category | 5 | Todo |
| US-013-04 | Customer API: register, login, profile | 5 | Todo |
| US-013-05 | Customer API: addresses CRUD | 5 | Todo |
| US-013-06 | API rate limiting | 3 | Todo |

---

### Sprint 14: REST API — Cart & Checkout
**Periode**: 01-12 Des 2026 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-014-01 | Cart API: add, update, remove, get | 8 | Todo |
| US-014-02 | Checkout API: shipping cost calculation | 5 | Todo |
| US-014-03 | Checkout API: process order + payment | 8 | Todo |
| US-014-04 | Order API: history, detail, track | 5 | Todo |
| US-014-05 | API documentation (Scramble / Scribe) | 5 | Todo |

---

### Sprint 15: SEO — Sitemap, OG Tags, Schema
**Periode**: 15-26 Des 2026 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-015-01 | Meta title + description di semua halaman | 5 | Todo |
| US-015-02 | Open Graph tags (og:title, og:desc, og:image) | 5 | Todo |
| US-015-03 | XML Sitemap generator (product, category, pages, blog) | 8 | Todo |
| US-015-04 | Canonical URL | 3 | Todo |
| US-015-05 | Schema.org: Product, Organization, BreadcrumbList | 5 | Todo |
| US-015-06 | Google Analytics + Facebook Pixel integration dari admin | 5 | Todo |
| US-015-07 | robots.txt dinamis | 2 | Todo |

---

### Sprint 16: Multi-channel Setup
**Periode**: 29 Des - 09 Jan 2027 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-016-01 | Migration: channels | 3 | Todo |
| US-016-02 | Multi-store: domain/prefix berbeda | 8 | Todo |
| US-016-03 | Channel-specific pricing | 5 | Todo |
| US-016-04 | Multi-currency (IDR default, tambah USD) | 5 | Todo |
| US-016-05 | Multi-locale (ID, EN) | 5 | Todo |

---

### Sprint 17: Reporting — Sales & Revenue
**Periode**: 12-23 Jan 2027 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-017-01 | Dashboard widgets: revenue, orders, customers count | 5 | Todo |
| US-017-02 | Sales report: harian, mingguan, bulanan (chart) | 8 | Todo |
| US-017-03 | Revenue summary: total, average order, top products | 5 | Todo |
| US-017-04 | Export report to Excel/CSV | 5 | Todo |

---

### Sprint 18: Reporting — Products & Customers
**Periode**: 26 Jan - 06 Feb 2027 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-018-01 | Product report: best seller, low stock, most viewed | 8 | Todo |
| US-018-02 | Customer report: new customers, top by spend | 5 | Todo |
| US-018-03 | Sales by category chart | 3 | Todo |
| US-018-04 | Report period filter + date range picker | 3 | Todo |

---

### Sprint 19: Email Templates
**Periode**: 09-20 Feb 2027 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-019-01 | Email templates: order confirmation (customer) | 5 | Todo |
| US-019-02 | Email templates: payment received (customer) | 3 | Todo |
| US-019-03 | Email templates: shipping update (customer) | 3 | Todo |
| US-019-04 | Email templates: new order notification (admin) | 3 | Todo |
| US-019-05 | Email template editor di admin (Blade preview) | 5 | Todo |
| US-019-06 | Queue email sending (database/redis queue) | 3 | Todo |
| US-019-07 | Invoice PDF generation + attach ke email | 5 | Todo |

---

### Sprint 20: Testing, Documentation & Launch
**Periode**: 23 Feb - 06 Mar 2027 (2 minggu)

#### User Stories

| Story ID | User Story | SP | Status |
|----------|------------|----|--------|
| US-020-01 | Unit test coverage >70% untuk modul baru | 8 | Todo |
| US-020-02 | Integration test API endpoints | 5 | Todo |
| US-020-03 | E2E test critical journeys (register, cart, checkout, order) | 8 | Todo |
| US-020-04 | Security audit (SQL injection, XSS, CSRF) | 5 | Todo |
| US-020-05 | Dokumentasi user guide admin + customer | 5 | Todo |
| US-020-06 | Performance load test (100 product, 1000 product) | 5 | Todo |
| US-020-07 | Database optimization: indexing, query analysis | 5 | Todo |
| US-020-08 | Launch readiness checklist | 3 | Todo |

---

## 4. Definition of Done (DoD)

### Story Level DoD
- [ ] Implementasi sesuai acceptance criteria
- [ ] Unit test untuk business logic
- [ ] Code review approved
- [ ] Manual QA testing passed (functional + UI)
- [ ] Tidak ada console error

### Sprint Level DoD
- [ ] Semua user stories completed
- [ ] Sprint goal tercapai
- [ ] Regression test passed (existing fitur tidak broken)
- [ ] Deployed ke staging
- [ ] Sprint review completed

### MVP Level DoD
- [ ] Semua P0 modul selesai
- [ ] Customer, Produk (EAV + Configurable), Inventory, Tax, Promotions
- [ ] Test E2E critical flow (register > add to cart > checkout > order)
- [ ] Frontend responsive

---

## 5. Critical Path & Dependencies

```
Sprint 1 (Customer) ───┐
                        ├──> Sprint 2 (Address, Wishlist, Reviews)
Sprint 3 (EAV) ────────┤
                        └──> Sprint 4 (Configurable Products)
                              │
                              ├──> Sprint 5-6 (Inventory)
                              │
                              ├──> Sprint 7 (Tax)
                              │
                              └──> Sprint 8-9 (Promotions)
                                    │
                              Sprint 10-11 (CMS) ──> can run parallel with 7-9
                              Sprint 12 (ACL) ────> can run after 10-11
                              Sprint 13-14 (API) ──> depends on Sprint 1-4
                              Sprint 15 (SEO) ────> can run parallel
                              Sprint 16 (Multi) ───> depends on 10-11
                              Sprint 17-18 (Reports) ──> depends on data
                              Sprint 19 (Email) ──> depends on order flow
```

### Parallel Paths
- Sprint 7 (Tax) bisa parallel dengan Sprint 5-6 (Inventory)
- Sprint 10-11 (CMS) parallel dengan 7-9 (Tax & Promo)
- Sprint 15 (SEO) independent
- Sprint 12 (ACL) bisa mulai setelah MVP

---

## 6. Risk Management

| Risk | Probability | Impact | Mitigasi |
|------|-------------|--------|----------|
| EAV system terlalu kompleks | Medium | High | Buat dulu untuk 1-2 attribute, scaling kemudian |
| Configurable product logic error (pricing) | Medium | High | Test semua kombinasi variant + price override |
| Tax calculation konflik dengan discount | Low | High | Prioritas: discount dulu, baru tax |
| Performance degrade karena banyak join | Medium | Medium | Query optimization, pagination, eager loading |
| Migration data existing rusak | Low | High | Backup database, test migration di staging dulu |
| Scope creep (minta fitur tambahan) | High | Medium | Strict per sprint, tambahan masuk backlog |

---

**Akhir dokumen Task Management — YClothes E-Commerce Upgrade v1.0**
