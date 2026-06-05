# PRD — YClothes E-Commerce Upgrade (Bagisto-Grade)

**Dokumen**: Product Requirements Document  
**Versi**: 1.0  
**Tanggal**: 2 Juni 2026  
**Status**: Draft  
**Referensi**: Bagisto (Laravel E-Commerce Platform), Midtrans, Laravel 13

---

## 1. Overview

### 1.1 Product Summary

YClothes adalah aplikasi e-commerce fashion berbasis Laravel 13 yang sudah berjalan dengan fitur dasar: katalog produk, keranjang, checkout, Midtrans, dan admin panel. Untuk menjadi setara Bagisto, perlu upgrade arsitektural dan penambahan modul-modul enterprise e-commerce.

### 1.2 Current State

**Teknologi saat ini:**
- Laravel 13, PHP 8.3
- Blade + Bootstrap 5 (frontend)
- Midtrans Payment Gateway
- MySQL Database

**Modul yang sudah ada:**
| Modul | Status | Fitur |
|-------|--------|-------|
| Katalog Produk | Done | Grid, search, filter kategori, fulltext index |
| Varian Produk | Done | Ukuran (sizes), warna (colors) via JSON |
| Keranjang (Cart) | Done | AJAX, tambah/hapus/update, throttle |
| Checkout | Done | Alamat, shipping cost, payment |
| Midtrans Payment | Done | Popup, notification handler, VA, Gopay, dll |
| Bank Transfer | Done | Manual, konfirmasi via WhatsApp |
| Order Tracking | Done | Via nomor pesanan + email, timeline |
| Admin Panel | Done | Products, Categories, Orders, Shipping, Payment Banks, Settings, Appearance |
| Flash Sale | Done | Countdown realtime, setting admin |
| Promo Banner | Done | Dinamis dari admin |
| Floating WA | Done | Tombol WhatsApp tetap |

### 1.3 Target: Bagisto-Grade

Bagisto memiliki fitur enterprise yang jadikan standar. Target upgrade meliputi:

| Area | YClothes (Sekarang) | Target (Setara Bagisto) |
|------|--------------------|-------------------------|
| Produk | Simple product | Simple, Configurable, Bundle, Grouped |
| Customer | Guest checkout only | Customer account (register, login, address book, wishlist, reviews) |
| Inventory | No stock tracking | Multi-warehouse, stock management, backorders |
| Pajak | None | Multi-tax rates, zones, included/excluded |
| Promo | Flash sale only | Coupons, cart rules, catalog rules, free shipping |
| CMS | About + Cara Belanja | Pages, blogs, navigation builder |
| SEO | Basic slug | Meta tags, sitemap, rich snippets, canonical |
| Reporting | None | Sales, products, customers, dashboard analytics |
| Roles | Single admin | Multi-role ACL, permissions |
| Multi | Single store | Multi-channel, multi-theme, multi-currency |
| API | None | REST API (headless ready) |
| Email | None | Email templates, transactional (invoice, shipping, register) |

---

## 2. Goals & Objectives

### 2.1 Business Goals

| # | Goal | Metrik |
|---|------|--------|
| BG-1 | Feature parity dengan Bagisto | 80% fitur enterprise e-commerce terpenuhi |
| BG-2 | Ready untuk production multi-tenant | Bisa dipakai untuk >1 toko |
| BG-3 | Headless-ready via REST API | API coverage >90% fitur |
| BG-4 | SEO optimized | PageSpeed 80+, Lighthouse SEO 90+ |

### 2.2 Technical Goals

| # | Goal | Detail |
|---|------|--------|
| TG-1 | Upgrade ke Laravel 13 stable | Pastikan kompatibel |
| TG-2 | Blade tetap (migration minimal) | Hindari rewrite total, upgrade bertahap |
| TG-3 | Database MySQL dengan indexing | Query < 200ms untuk semua halaman |
| TG-4 | Package modular | Setiap modul terpisah, bisa diaktifkan/dinonaktifkan |

---

## 3. Current Architecture Analysis

### 3.1 Database Schema (Existing)

```
users              -> id, name, email, password, is_admin
categories         -> id, name, slug, image, order
products           -> id, category_id, name, slug, description, price, sale_price,
                      image, images (json), sizes (json), colors (json), badge,
                      weight, is_featured, views, fulltext index
orders             -> id, order_number, customer_name/phone/email, shipping_address/city,
                      shipping_cost, total_price, grand_total, payment_method/status,
                      payment_due_at, paid_at, bank_name, access_token, timestamps
order_items        -> id, order_id, product_id, product_name, product_price, qty,
                      subtotal, size, color, timestamps
payment_banks      -> id, bank_name, account_number, account_name, is_active
shipping_costs     -> id, city_name, cost, cost_per_kg, is_active
settings           -> id, key, value
sessions, cache, jobs (default Laravel)
```

### 3.2 Perbandingan dengan Bagisto

| Aspek | YClothes | Bagisto |
|-------|----------|---------|
| Product Types | Simple | Simple, Configurable, Bundle, Grouped, Virtual, Downloadable |
| Attributes | Hardcoded (sizes, colors) | EAV (Entity-Attribute-Value) — dynamic attributes |
| Customer | None (guest) | Registration, addresses, wishlist, reviews, cart persistence |
| Inventory | None | Multi-warehouse, stock quantity, reorder level, backorders |
| Tax | None | Tax rates, tax categories, zones, included/excluded |
| Channel | Single store | Multi-channel, multi-locale, multi-currency |
| Promotions | Flash sale | Cart rules, catalog rules, coupons, free shipping |
| CMS | 2 pages | Pages, blogs, slider, navigation builder |
| SEO | Basic slug | Meta title/desc/keywords, URL rewrite, sitemap, rich snippets |
| User Roles | admin flag | ACL, roles, permissions per resource |
| Reporting | None | Sales report, product report, customer report, dashboard widgets |
| REST API | None | Product API, Cart API, Checkout API, Customer API |
| Email | None (WA manual) | Email templates, queue, transactional mail |

---

## 4. Modul & Feature Requirements

### 4.1 Product Enhancement

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-PROD-001 | Product attributes system (EAV): admin dapat membuat field kustom (ukuran, warna, bahan, merk, dll) dinamis | P0 |
| REQ-PROD-002 | Configurable product: induk dengan multiple variasi (XL-Hitam = SKU beda, stock beda, harga beda) | P0 |
| REQ-PROD-003 | Product categories: sub-kategori (parent-child), tree view, multi-category per produk | P0 |
| REQ-PROD-004 | Product gallery: multiple image dengan sortable, zoom, thumbnail | P1 |
| REQ-PROD-005 | Product tags / labels: "New", "Best Seller", "Limited" — dinamis | P1 |
| REQ-PROD-006 | Product reviews & ratings: bintang 1-5, review text, verified purchase badge | P1 |
| REQ-PROD-007 | Cross-sell, up-sell, related products | P1 |
| REQ-PROD-008 | Product bundles: bundle produk dengan harga khusus (hemat Rp X) | P2 |
| REQ-PROD-009 | Downloadable product: digital product (opsional, untuk future) | P3 |

### 4.2 Customer Account

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-CUST-001 | Customer registration & login (email + password, Google OAuth) | P0 |
| REQ-CUST-002 | Customer profile: name, email, phone, avatar | P0 |
| REQ-CUST-003 | Address book: multiple alamat (utama, pengiriman, tagihan) | P0 |
| REQ-CUST-004 | Order history: daftar pesanan + detail | P0 |
| REQ-CUST-005 | Wishlist: tambah/hapus product | P1 |
| REQ-CUST-006 | Product reviews dari customer yang sudah login | P1 |
| REQ-CUST-007 | Cart persistence: keranjang tetap tersimpan walau logout/login | P1 |
| REQ-CUST-008 | Forgot password / reset password | P1 |
| REQ-CUST-009 | Email verification (wajib verifikasi sebelum checkout) | P1 |
| REQ-CUST-010 | Download invoice PDF dari history | P2 |

### 4.3 Inventory & Warehouse

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-INV-001 | Stock quantity per product (configurable: track stock atau tidak) | P0 |
| REQ-INV-002 | Configurable product: stock per variasi (sku: XL-Hitam = 5, M-Hitam = 10) | P0 |
| REQ-INV-003 | Multi-warehouse: gudang pusat, gudang cabang, masing-masing punya stock sendiri | P1 |
| REQ-INV-004 | Stock movement log: barang masuk, keluar, transfer antar gudang | P1 |
| REQ-INV-005 | Low stock alert: threshold configurable, notifikasi ke admin | P1 |
| REQ-INV-006 | Backorder: allow checkout walau stock habis, dengan estimasi arrival | P2 |
| REQ-INV-007 | Stock adjustment: admin bisa koreksi stock manual dengan reason | P1 |

### 4.4 Tax Management

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-TAX-001 | Tax rates: persentase per kategori produk | P1 |
| REQ-TAX-002 | Tax zones: berbeda untuk provinsi/kota tertentu | P1 |
| REQ-TAX-003 | Tax included/excluded: pilih harga sudah termasuk pajak atau belum | P1 |
| REQ-TAX-004 | PPN 11% default untuk Indonesia | P1 |
| REQ-TAX-005 | Tax display: subtotal, tax, grand total di keranjang & checkout | P1 |
| REQ-TAX-006 | PPh 22: untuk produk tertentu (jika applicable) | P2 |

### 4.5 Promotions & Marketing

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-PROMO-001 | Cart rules: diskon berdasarkan total belanja (min Rp X, diskon Y%) | P0 |
| REQ-PROMO-002 | Catalog rules: diskon langsung ke harga produk (tanpa kupon) | P1 |
| REQ-PROMO-003 | Coupon codes: kode unik, usage limit, per customer limit | P1 |
| REQ-PROMO-004 | Free shipping: syarat minimal belanja | P1 |
| REQ-PROMO-005 | Buy X Get Y: beli X dapat diskon Y | P2 |
| REQ-PROMO-006 | Tiered pricing: quantity discount (beli 3+, diskon 10%) | P2 |

### 4.6 CMS (Content Management)

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-CMS-001 | Pages: CRUD dengan WYSIWYG editor, slug, status publish/draft | P0 |
| REQ-CMS-002 | Blog: post dengan kategori, tags, author, comments | P1 |
| REQ-CMS-003 | Navigation builder: multi-level menu (header, footer) drag-drop | P1 |
| REQ-CMS-004 | Image slider: untuk homepage banner, multiple slides | P1 |
| REQ-CMS-005 | FAQ: kategori, question, answer, accordion | P2 |
| REQ-CMS-006 | Testimonial: CRUD, tampil di homepage | P2 |
| REQ-CMS-007 | Newsletter signup: form email, export CSV | P2 |

### 4.7 SEO

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-SEO-001 | Meta title + meta description per halaman (product, category, page) | P0 |
| REQ-SEO-002 | Open Graph tags: og:title, og:description, og:image | P1 |
| REQ-SEO-003 | XML Sitemap otomatis: product, category, pages, blog | P1 |
| REQ-SEO-004 | Canonical URL | P1 |
| REQ-SEO-005 | Schema.org / Rich snippets: Product, Organization, BreadcrumbList | P1 |
| REQ-SEO-006 | URL rewrites: custom redirect 301 | P2 |
| REQ-SEO-007 | robots.txt dinamis dari admin | P2 |
| REQ-SEO-008 | Google Analytics / Facebook Pixel integration dari admin | P1 |

### 4.8 Multi-Channel

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-MULTI-001 | Multi-store: >1 toko dalam 1 instalasi (domain/prefix berbeda) | P2 |
| REQ-MULTI-002 | Multi-currency: IDR default, bisa pilih mata uang lain | P2 |
| REQ-MULTI-003 | Multi-locale: Bahasa Indonesia (default), English | P2 |
| REQ-MULTI-004 | Channel-specific pricing: harga berbeda per channel | P3 |

### 4.9 Admin Roles & ACL

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-ACL-001 | Roles: admin, staff, finance, logistic — bisa buat custom role | P1 |
| REQ-ACL-002 | Permissions: per modul (products, orders, customers, settings) | P1 |
| REQ-ACL-003 | Staff management: CRUD staff dengan role assignment | P1 |
| REQ-ACL-004 | Activity log: catat semua aksi admin (siapa, apa, kapan) | P1 |

### 4.10 Reporting & Analytics

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-REPORT-001 | Sales report: harian, mingguan, bulanan + grafik | P1 |
| REQ-REPORT-002 | Revenue summary: total revenue, average order, top products | P1 |
| REQ-REPORT-003 | Product report: best seller, low stock, most viewed | P1 |
| REQ-REPORT-004 | Customer report: new customers, top customers by spend | P1 |
| REQ-REPORT-005 | Export report to Excel/CSV | P2 |
| REQ-REPORT-006 | Dashboard widgets: ringkasan real-time | P1 |

### 4.11 REST API

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-API-001 | Product API: list, detail, search, filter | P1 |
| REQ-API-002 | Cart API: add, update, remove, get | P1 |
| REQ-API-003 | Checkout API: shipping cost, process order | P1 |
| REQ-API-004 | Customer API: register, login, profile, addresses | P1 |
| REQ-API-005 | Order API: history, detail, track | P1 |
| REQ-API-006 | API authentication via Laravel Sanctum | P1 |
| REQ-API-007 | API rate limiting | P1 |
| REQ-API-008 | API documentation (Scribe / Scramble) | P2 |

### 4.12 Email & Notification

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-EMAIL-001 | Order confirmation email (customer) | P1 |
| REQ-EMAIL-002 | Payment received email (customer) | P1 |
| REQ-EMAIL-003 | Shipping update email (customer) | P1 |
| REQ-EMAIL-004 | New order notification email (admin) | P1 |
| REQ-EMAIL-005 | Welcome email after registration | P2 |
| REQ-EMAIL-006 | Email templates: editable dari admin (Blade + preview) | P2 |

### 4.13 Order Enhancement

| REQ ID | Requirement | Prioritas |
|--------|-------------|-----------|
| REQ-ORDER-001 | Order status workflow: pending > paid > processing > shipped > delivered > completed | P0 |
| REQ-ORDER-002 | Order notes: admin dapat menambah catatan internal | P1 |
| REQ-ORDER-003 | Partial shipment: pecah pengiriman per item | P2 |
| REQ-ORDER-004 | Invoice generation (PDF) | P1 |
| REQ-ORDER-005 | Cancellation: customer dapat batalkan pesanan (sebelum diproses) | P1 |
| REQ-ORDER-006 | Refund: admin dapat refund partial/full | P2 |

---

## 5. Database Schema (New Tables)

```sql
-- Product Attributes (EAV)
CREATE TABLE attribute_families (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE attributes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('text','textarea','select','multiselect','boolean','date','decimal','price') NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    is_filterable BOOLEAN DEFAULT FALSE,
    validation VARCHAR(50) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE attribute_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
);

CREATE TABLE product_attribute_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    attribute_id BIGINT UNSIGNED NOT NULL,
    value TEXT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
    UNIQUE (product_id, attribute_id)
);

-- Configurable Products
CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_product_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15,2) NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255) NULL,
    attributes JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Customers
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    avatar VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE customer_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(50) DEFAULT 'Rumah',
    recipient_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    street_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10) NULL,
    is_default BOOLEAN DEFAULT FALSE,
    type ENUM('shipping','billing','both') DEFAULT 'both',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Wishlist
CREATE TABLE wishlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE (customer_id, product_id)
);

-- Reviews
CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Inventory
CREATE TABLE inventories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    stock INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    type ENUM('in','out','transfer','adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(100) NULL,
    reference_id BIGINT UNSIGNED NULL,
    reason TEXT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tax
CREATE TABLE tax_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    type ENUM('percentage','fixed') DEFAULT 'percentage',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE tax_rate_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tax_rate_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    FOREIGN KEY (tax_rate_id) REFERENCES tax_rates(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Promotions
CREATE TABLE cart_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    coupon_code VARCHAR(50) NULL UNIQUE,
    uses_per_coupon INT DEFAULT 0,
    uses_per_customer INT DEFAULT 0,
    discount_type ENUM('percentage','fixed','free_shipping') NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL,
    min_order_amount DECIMAL(15,2) NULL,
    max_discount DECIMAL(15,2) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- CMS
CREATE TABLE cms_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    status ENUM('draft','published') DEFAULT 'draft',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE blog_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NULL,
    excerpt TEXT NULL,
    featured_image VARCHAR(255) NULL,
    author VARCHAR(100) NULL,
    status ENUM('draft','published') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Admin Roles & ACL
CREATE TABLE admin_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    permissions JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Channel
CREATE TABLE channels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    domain VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 6. Non-Functional Requirements

| REQ ID | Requirement | Target |
|--------|-------------|--------|
| REQ-NFR-001 | Query time untuk product listing < 200ms | Performance |
| REQ-NFR-002 | API response time < 500ms | Performance |
| REQ-NFR-003 | 1.000 product page load < 2 detik | Performance |
| REQ-NFR-004 | Checkout flow < 5 detik | Performance |
| REQ-NFR-005 | SEO: PageSpeed 80+, Lighthouse SEO 90+ | SEO |
| REQ-NFR-006 | Mobile responsive: 320px - 1920px | UX |
| REQ-NFR-007 | Waktu render admin < 2 detik | UX |
| REQ-NFR-008 | API rate limit: 60 req/menit | Security |
| REQ-NFR-009 | Password policy: min 8 karakter | Security |
| REQ-NFR-010 | Data backup: database harian | Reliability |

---

## 7. UI/UX Specification

### 7.1 Frontend (Customer)

Menggunakan framework yang sudah ada (Blade + Bootstrap 5) dengan upgrade:

| Halaman | Upgrade |
|---------|---------|
| Home | Slider dinamis, featured products, blog terbaru, newsletter |
| Product List | Filter: kategori, harga range, ukuran, warna (dari EAV), sort, grid/list toggle, infinite scroll / pagination |
| Product Detail | Gallery zoom, variant selector (configurable), stock indicator, reviews, cross-sell, wishlist toggle |
| Cart | Cart rules applied, tax breakdown, shipping estimate, coupon input |
| Checkout | Customer login/guest toggle, address book, shipping method, payment method, order summary |
| Customer Account | Dashboard, profile, addresses, orders, wishlist, reviews |

### 7.2 Admin Panel

Menggunakan layout admin yang sudah ada dengan sidebar expand:

```
Dashboard (widgets: revenue, orders, customers)
Products
  - All Products
  - Attributes (EAV)
  - Attributes Families
  - Categories
  - Reviews
Customers
  - All Customers
  - Customer Groups (future)
  - Reviews
Sales
  - Orders
  - Shipments
  - Refunds (future)
  - Invoices
Inventory
  - Stock
  - Warehouses
  - Stock Movements
Promotions
  - Cart Rules
  - Catalog Rules
  - Coupons
CMS
  - Pages
  - Blog
  - Slider
  - Navigation
  - FAQ
Reports
  - Sales
  - Products
  - Customers
Settings
  - General
  - Tax
  - Shipping
  - Payment
  - Email
  - SEO
  - Roles & Permissions
  - Staff
System
  - Activity Log
  - Clear Cache
  - Maintenance
```

---

## 8. Modul Roadmap

| Phase | Modul | Sprint |
|-------|-------|--------|
| **Phase 1** | Customer Account + Reviews + Wishlist | 1-2 |
| | Attribute EAV System + Configurable Products | 3-4 |
| **Phase 2** | Inventory + Warehouse + Stock Management | 5-6 |
| | Tax Management | 7 |
| | Promotions & Coupons | 8-9 |
| **Phase 3** | CMS (Pages, Blog, Slider, Nav) | 10-11 |
| | Admin ACL + Roles + Activity Log | 12 |
| **Phase 4** | REST API | 13-14 |
| | SEO (Sitemap, OG, Schema) | 15 |
| | Multi-channel / Multi-currency | 16 |
| **Phase 5** | Reporting & Analytics | 17-18 |
| | Email Templates | 19 |
| | Testing & Launch | 20 |

---

## 9. Daftar Modul per Prioritas

### 9.1 P0 — MVP After Upgrade (Harus Ada)

| Modul | Reason | Estimasi |
|-------|--------|----------|
| Customer Account | Basic requirement toko modern | 2 sprint |
| EAV Attributes | Product flexibility | 2 sprint |
| Configurable Product | Fashion e-commerce wajib | 1 sprint |
| Inventory / Stock | Operation utama | 1 sprint |
| Tax Management | Compliance (PPN) | 1 sprint |
| Promotions & Coupons | Marketing tool | 2 sprint |
| **Total** | **6-7 modul** | **~9 sprint (18 minggu)** |

### 9.2 P1 — Setelah MVP

| Modul | Reason |
|-------|--------|
| CMS (Pages, Blog) | Content marketing |
| Admin ACL | Multi-staff management |
| REST API | Headless, mobile app future |
| SEO | Organic traffic |
| Reporting | Business insight |

### 9.3 P2-P3 — Enhancement

| Modul | Reason |
|-------|--------|
| Multi-channel | Multiple store |
| Multi-currency | Internasional |
| Email Templates | Customer experience |
| Downloadable Product | Digital product future |

---

## 10. Risk Management

| Risk | Prob | Impact | Mitigasi |
|------|------|--------|----------|
| Migration data dari schema lama ke EAV | Medium | High | Buat migration script + test dengan data dummy |
| Performance dengan EAV (banyak join) | Medium | High | Indexing, cache query result, eager loading |
| Configurable product complex logic | High | Medium | Test coverage >80% untuk variant pricing + stock |
| Customer migration (guest to account) | Medium | Low | Guest checkout tetap dipertahankan |
| Bootstrap 5 limitations untuk UI | Low | Medium | Custom CSS, tetap kompatibel |
| API security (auth, rate limit) | Low | High | Sanctum + throttle built-in |

---

**Akhir dokumen PRD — YClothes E-Commerce Upgrade to Bagisto-Grade v1.0**
