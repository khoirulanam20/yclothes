export type HelpSection = {
    title: string;
    paragraphs?: string[];
    steps?: string[];
    list?: string[];
};

export const attributeHelp: HelpSection = {
    title: 'Apa itu Atribut?',
    paragraphs: [
        'Atribut adalah jenis informasi produk yang bisa diisi per item, misalnya Ukuran, Warna, atau Berat.',
        'Atribut multiselect atau warna (code color) bisa ditandai sebagai sumbu varian di Keluarga Atribut.',
    ],
    steps: [
        'Buka Katalog → Atribut → Tambah.',
        'Isi Nama (tampilan) dan Code (unik, lowercase).',
        'Pilih tipe input: multiselect untuk opsi ganda (ukuran, berat, dll.), atau warna dengan code color.',
        'Tambahkan opsi pilihan (S, M, L atau 400 gr, 350 gr, dll.).',
        'Centang Filterable jika ingin muncul di filter toko.',
    ],
};

export const attributeFamilyHelp: HelpSection = {
    title: 'Apa itu Keluarga Atribut?',
    paragraphs: [
        'Keluarga atribut adalah template yang menentukan field apa saja yang muncul saat buat/edit produk.',
        'Centang "Menghasilkan varian" pada atribut yang menjadi sumbu kombinasi SKU (mis. ukuran, warna, berat).',
    ],
    steps: [
        'Buat atribut terlebih dahulu di menu Atribut.',
        'Buka Keluarga Atribut → Tambah, beri nama (mis. Fashion Default).',
        'Centang atribut yang relevan, lalu centang "Menghasilkan varian" untuk sumbu varian.',
        'Saat buat produk, pilih keluarga ini — form akan menampilkan field sesuai template.',
    ],
    list: [
        'Simple (barang tunggal): keluarga boleh tanpa sumbu varian.',
        'Configurable (barang varian): keluarga harus punya minimal satu sumbu varian.',
        'Seeder default sudah menyiapkan keluarga "Fashion Default" dengan ukuran + warna.',
    ],
};

export const productCreateHelp: HelpSection = {
    title: 'Sebelum buat produk',
    list: [
        'Pastikan sudah ada Keluarga Atribut dengan field yang dibutuhkan.',
        'Untuk produk varian, keluarga harus punya minimal satu atribut sumbu varian.',
        'SKU harus unik — dipakai sebagai identitas stok.',
        'Setelah simpan, Anda akan diarahkan ke halaman edit lengkap.',
    ],
};

export const productVariantHelp: HelpSection = {
    title: 'Cara kerja varian',
    paragraphs: [
        'Sistem otomatis membuat kombinasi dari nilai atribut sumbu varian yang Anda pilih di tab Atribut.',
        'Setiap baris varian bisa punya SKU, harga, stok, dan gambar sendiri.',
    ],
    list: [
        'Isi tab Atribut dulu, simpan produk, lalu kembali ke tab Varian.',
        'Nonaktifkan varian yang tidak dijual dengan toggle Aktif.',
        'Kosongkan harga varian untuk memakai harga produk induk.',
    ],
};
