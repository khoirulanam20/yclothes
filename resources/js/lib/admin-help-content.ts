export type HelpSection = {
    title: string;
    paragraphs?: string[];
    steps?: string[];
    list?: string[];
};

export const attributeHelp: HelpSection = {
    title: 'Apa itu Atribut?',
    paragraphs: [
        'Atribut adalah jenis informasi produk yang bisa diisi per item, misalnya Ukuran, Warna, atau Bahan.',
        'Atribut dengan code size dan color dipakai sistem untuk menghasilkan varian produk (SKU berbeda per kombinasi ukuran × warna).',
    ],
    steps: [
        'Buka Katalog → Atribut → Tambah.',
        'Isi Nama (tampilan) dan Code (unik, lowercase). Untuk varian gunakan code size atau color.',
        'Pilih tipe input: pilihan ganda untuk ukuran, select/multiselect untuk warna.',
        'Tambahkan opsi pilihan (S, M, L atau Hitam, Putih, dll.).',
        'Centang Filterable jika ingin muncul di filter toko.',
    ],
};

export const attributeFamilyHelp: HelpSection = {
    title: 'Apa itu Keluarga Atribut?',
    paragraphs: [
        'Keluarga atribut adalah template yang menentukan field apa saja yang muncul saat buat/edit produk.',
        'Contoh: keluarga "Fashion Default" berisi Ukuran + Warna + Bahan.',
    ],
    steps: [
        'Buat atribut terlebih dahulu di menu Atribut.',
        'Buka Keluarga Atribut → Tambah, beri nama (mis. Fashion Default).',
        'Centang atribut yang relevan. Untuk produk dengan varian, sertakan Ukuran (size) dan Warna (color).',
        'Saat buat produk, pilih keluarga ini — form akan menampilkan field sesuai template.',
    ],
    list: [
        'Simple (barang tunggal): keluarga boleh tanpa size/color.',
        'Configurable (barang varian): keluarga harus punya size dan/atau color.',
        'Seeder default sudah menyiapkan keluarga "Fashion Default".',
    ],
};

export const productCreateHelp: HelpSection = {
    title: 'Sebelum buat produk',
    list: [
        'Pastikan sudah ada Keluarga Atribut dengan field yang dibutuhkan.',
        'Untuk produk varian, keluarga harus memuat atribut size dan/atau color.',
        'SKU harus unik — dipakai sebagai identitas stok.',
        'Setelah simpan, Anda akan diarahkan ke halaman edit lengkap.',
    ],
};

export const productVariantHelp: HelpSection = {
    title: 'Cara kerja varian',
    paragraphs: [
        'Sistem otomatis membuat kombinasi dari nilai atribut Ukuran × Warna yang Anda pilih di tab Atribut.',
        'Setiap baris varian bisa punya SKU, harga, stok, dan gambar sendiri.',
    ],
    list: [
        'Isi tab Atribut dulu, simpan produk, lalu kembali ke tab Varian.',
        'Nonaktifkan varian yang tidak dijual dengan toggle Aktif.',
        'Kosongkan harga varian untuk memakai harga produk induk.',
    ],
};
