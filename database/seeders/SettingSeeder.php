<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'wa_number'],
            ['value' => '6280000000000'],
        );

        Setting::firstOrCreate(
            ['key' => 'brand_name'],
            ['value' => 'YClothes'],
        );

        Setting::firstOrCreate(
            ['key' => 'color_gold'],
            ['value' => '#C2A56D'],
        );

        Setting::firstOrCreate(
            ['key' => 'color_accent'],
            ['value' => '#547A95'],
        );

        collect(['social_instagram', 'social_facebook', 'social_tiktok'])
            ->each(fn ($key) => Setting::firstOrCreate(['key' => $key], ['value' => null]));

        Setting::firstOrCreate(
            ['key' => 'flash_sale_ends_at'],
            ['value' => now()->endOfDay()->format('Y-m-d\TH:i')],
        );

        Setting::firstOrCreate(
            ['key' => 'site_title'],
            ['value' => 'YClothes'],
        );

        Setting::firstOrCreate(
            ['key' => 'site_description'],
            ['value' => 'Toko fashion premium untuk gaya terbaikmu. Temukan koleksi pakaian, aksesoris, dan sepatu terbaru.'],
        );

        Setting::firstOrCreate(
            ['key' => 'hero_title'],
            ['value' => 'Koleksi Terbaru<br>Musim Ini'],
        );

        Setting::firstOrCreate(
            ['key' => 'hero_subtitle'],
            ['value' => 'Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.'],
        );

        Setting::firstOrCreate(
            ['key' => 'banner_title'],
            ['value' => 'Belanja Nyaman Kualitas Aman'],
        );

        Setting::firstOrCreate(
            ['key' => 'banner_text'],
            ['value' => 'Temukan gaya terbaikmu dengan koleksi fashion premium. Dari kasual hingga formal, semua ada di sini.'],
        );

        Setting::firstOrCreate(
            ['key' => 'cta_text'],
            ['value' => 'Shop Now →'],
        );

        Setting::firstOrCreate(
            ['key' => 'cta_link'],
            ['value' => '/products'],
        );

        Setting::firstOrCreate(
            ['key' => 'store_location'],
            ['value' => 'Makassar'],
        );

        Setting::firstOrCreate(
            ['key' => 'promo_bar_text'],
            ['value' => 'Free Ongkir Pembelian > Rp 200rb'],
        );

        Setting::firstOrCreate(
            ['key' => 'tax_included'],
            ['value' => '0'],
        );

        Setting::firstOrCreate(
            ['key' => 'low_stock_threshold'],
            ['value' => '5'],
        );

        Setting::firstOrCreate(
            ['key' => 'banner_button'],
            ['value' => 'Belanja Sekarang'],
        );

        Setting::firstOrCreate(
            ['key' => 'banner_link'],
            ['value' => '/products'],
        );

        Setting::firstOrCreate(
            ['key' => 'cara_belanja_content'],
            ['value' => '<p>Berikut adalah langkah-langkah mudah untuk berbelanja di Website Kami.</p>
<hr>
<ol>
<li>
<h3>Pilih Produk</h3>
<p>Jelajahi katalog produk kami di halaman <a href="/products">Produk</a>. Kamu bisa filter berdasarkan kategori atau mencari produk favoritmu.</p>
</li>
<li>
<h3>Pilih Varian</h3>
<p>Setelah menemukan produk yang diinginkan, pilih ukuran dan warna yang sesuai. Pastikan untuk memeriksa panduan ukuran jika tersedia.</p>
</li>
<li>
<h3>Masukkan ke Keranjang</h3>
<p>Klik tombol "Tambah ke Keranjang" dan produk akan tersimpan di keranjang belanja kamu. Kamu bisa lanjut berbelanja atau langsung checkout.</p>
</li>
<li>
<h3>Checkout</h3>
<p>Masukkan alamat pengiriman dan pilih metode pembayaran. Kami menerima transfer bank dan pembayaran online melalui Midtrans.</p>
</li>
<li>
<h3>Konfirmasi Pembayaran</h3>
<p>Lakukan pembayaran sesuai petunjuk dan konfirmasi melalui WhatsApp jika menggunakan transfer bank. Pesanan akan segera diproses setelah pembayaran dikonfirmasi.</p>
</li>
<li>
<h3>Terima Pesanan</h3>
<p>Pesanan akan dikirim dan kamu bisa melacak status pengiriman melalui halaman <a href="/order/track">Lacak Pesanan</a>.</p>
</li>
</ol>
<hr>
<p>Ada pertanyaan? Hubungi kami via WhatsApp untuk bantuan lebih lanjut.</p>'],
        );

        Setting::firstOrCreate(
            ['key' => 'about_content'],
            ['value' => '
<h3>Visi Kami</h3>
<p>Menjadi destinasi fashion terdepan di Indonesia yang memberikan pengalaman belanja online terbaik dengan produk berkualitas dan pelayanan memuaskan.</p>
<h3>Misi Kami</h3>
<ul>
<li>Menyediakan produk fashion berkualitas dengan harga terjangkau</li>
<li>Memberikan pengalaman belanja online yang mudah, aman, dan nyaman</li>
<li>Terus berinovasi mengikuti tren fashion terkini</li>
<li>Memberikan pelayanan pelanggan terbaik</li>
</ul>
<hr>
<h3>Mengapa Memilih?</h3>
<ul>
<li>Kualitas Terjamin: Setiap produk melalui proses quality control ketat sebelum dikirim.</li>
<li>Pengiriman Cepat: Dikirim dari kota terdekat dengan estimasi 2-4 hari kerja.</li>
<li>Layanan Pelanggan: Tim support siap membantu melalui WhatsApp selama jam kerja.</li>
</ul>
'],
        );
    }
}
