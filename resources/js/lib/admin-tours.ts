import type { AdminTourKey } from '@/lib/admin-tour-keys';
import type { TourPageVariant } from '@/lib/admin-tour-routes';

export type AdminTourStep = {
    element: string;
    title: string;
    description: string;
    variants?: TourPageVariant[];
};

export type AdminTourDefinition = {
    key: AdminTourKey;
    steps: AdminTourStep[];
};

function navStep(tourKey: AdminTourKey, menuLabel: string): AdminTourStep {
    return {
        element: `[data-tour="nav-${tourKey}"]`,
        title: `Menu ${menuLabel}`,
        description: `Anda berada di bagian ${menuLabel}. Gunakan menu sidebar kiri untuk berpindah antar modul admin.`,
    };
}

function commonSteps(tourKey: AdminTourKey, menuLabel: string): AdminTourStep[] {
    return [
        navStep(tourKey, menuLabel),
        {
            element: '[data-tour="breadcrumb"]',
            title: 'Jejak navigasi',
            description: 'Breadcrumb menunjukkan posisi Anda di dalam admin dan memudahkan kembali ke halaman sebelumnya.',
        },
        {
            element: '[data-tour="notifications"]',
            title: 'Notifikasi admin',
            description: 'Pantau aktivitas penting seperti pesanan baru, retur, atau ulasan yang perlu ditinjau.',
        },
    ];
}

function indexSteps(menuLabel: string): AdminTourStep[] {
    return [
        {
            element: '[data-tour="page-header"]',
            title: `Halaman ${menuLabel}`,
            description: `Ringkasan dan judul halaman ${menuLabel}. Dari sini Anda mengelola data utama modul ini.`,
            variants: ['index'],
        },
        {
            element: '[data-tour="create-button"]',
            title: 'Tambah data baru',
            description: 'Klik tombol ini untuk membuat entri baru di modul ini.',
            variants: ['index'],
        },
        {
            element: '[data-tour="data-table"]',
            title: 'Daftar data',
            description: 'Tabel berisi data yang sudah ada. Gunakan aksi di setiap baris untuk mengedit atau menghapus.',
            variants: ['index'],
        },
    ];
}

function formSteps(menuLabel: string): AdminTourStep[] {
    return [
        {
            element: '[data-tour="page-header"]',
            title: `Form ${menuLabel}`,
            description: 'Isi informasi yang diperlukan pada form berikut. Field bertanda wajib harus diisi sebelum menyimpan.',
            variants: ['create', 'edit'],
        },
        {
            element: '[data-tour="form-main"]',
            title: 'Area form',
            description: 'Kelompok field utama untuk data yang akan disimpan ke sistem.',
            variants: ['create', 'edit'],
        },
        {
            element: '[data-tour="form-submit"]',
            title: 'Simpan perubahan',
            description: 'Setelah selesai mengisi, simpan data atau batalkan untuk kembali ke daftar.',
            variants: ['create', 'edit'],
        },
    ];
}

function showSteps(menuLabel: string): AdminTourStep[] {
    return [
        {
            element: '[data-tour="page-header"]',
            title: `Detail ${menuLabel}`,
            description: 'Informasi lengkap entri yang dipilih ditampilkan di sini.',
            variants: ['show'],
        },
        {
            element: '[data-tour="order-status"]',
            title: 'Status & alur',
            description: 'Pantau status saat ini dan langkah proses yang tersedia untuk entri ini.',
            variants: ['show'],
        },
        {
            element: '[data-tour="order-actions"]',
            title: 'Aksi tersedia',
            description: 'Tombol aksi di sini digunakan untuk memproses, memperbarui, atau menyelesaikan alur kerja.',
            variants: ['show'],
        },
    ];
}

function buildTour(
    key: AdminTourKey,
    menuLabel: string,
    extra: AdminTourStep[] = [],
): AdminTourDefinition {
    return {
        key,
        steps: [...commonSteps(key, menuLabel), ...indexSteps(menuLabel), ...formSteps(menuLabel), ...extra],
    };
}

export const adminTours: Record<AdminTourKey, AdminTourDefinition> = {
    dashboard: {
        key: 'dashboard',
        steps: [
            ...commonSteps('dashboard', 'Dasbor'),
            {
                element: '[data-tour="dashboard-stats"]',
                title: 'Ringkasan toko',
                description: 'Kartu statistik menampilkan jumlah produk, kategori, pesanan, dan pesanan menunggu tindakan.',
                variants: ['index'],
            },
            {
                element: '[data-tour="dashboard-orders"]',
                title: 'Pesanan terbaru',
                description: 'Pantau pesanan masuk terbaru dan buka detail langsung dari daftar ini.',
                variants: ['index'],
            },
            {
                element: '[data-tour="dashboard-activity"]',
                title: 'Aktivitas terbaru',
                description: 'Log singkat aktivitas admin membantu melacak perubahan penting di toko.',
                variants: ['index'],
            },
        ],
    },
    orders: buildTour('orders', 'Pesanan', showSteps('Pesanan')),
    returns: buildTour('returns', 'Retur', [
        ...showSteps('Retur'),
        {
            element: '[data-tour="returns-policy"]',
            title: 'Kebijakan retur',
            description: 'Atur teks kebijakan retur yang ditampilkan ke pelanggan di halaman khusus ini.',
            variants: ['special'],
        },
    ]),
    reviews: buildTour('reviews', 'Ulasan'),
    products: buildTour('products', 'Produk'),
    categories: buildTour('categories', 'Kategori'),
    attributes: buildTour('attributes', 'Atribut'),
    'attribute-families': buildTour('attribute-families', 'Keluarga Atribut'),
    'cms-pages': buildTour('cms-pages', 'Halaman CMS', [
        {
            element: '[data-tour="cms-builder-toolbar"]',
            title: 'Toolbar builder',
            description: 'Simpan halaman, atur metadata, dan kelola status publikasi dari toolbar atas.',
            variants: ['special'],
        },
        {
            element: '[data-tour="cms-builder-canvas"]',
            title: 'Kanvas halaman',
            description: 'Seret komponen ke area ini untuk menyusun layout halaman CMS.',
            variants: ['special'],
        },
        {
            element: '[data-tour="cms-builder-components"]',
            title: 'Komponen',
            description: 'Pilih blok konten (teks, gambar, tombol, dll.) dari panel komponen.',
            variants: ['special'],
        },
    ]),
    'blog-posts': buildTour('blog-posts', 'Blog'),
    navigation: buildTour('navigation', 'Navigasi'),
    faq: buildTour('faq', 'FAQ', [
        {
            element: '[data-tour="page-header"]',
            title: 'Daftar pertanyaan FAQ',
            description: 'Kelola pertanyaan dan jawaban untuk kategori FAQ yang dipilih.',
            variants: ['nested'],
        },
        {
            element: '[data-tour="create-button"]',
            title: 'Tambah pertanyaan',
            description: 'Buat entri FAQ baru di dalam kategori ini.',
            variants: ['nested'],
        },
        {
            element: '[data-tour="data-table"]',
            title: 'Item FAQ',
            description: 'Tabel berisi pertanyaan yang sudah ada. Gunakan aksi untuk mengedit atau menghapus.',
            variants: ['nested'],
        },
    ]),
    inventories: buildTour('inventories', 'Stok'),
    warehouses: buildTour('warehouses', 'Gudang'),
    'stock-movements': buildTour('stock-movements', 'Pergerakan Stok', [
        {
            element: '[data-tour="stock-special-form"]',
            title: 'Form pergerakan stok',
            description: 'Isi produk, gudang, dan jumlah untuk penyesuaian atau transfer stok.',
            variants: ['special'],
        },
        {
            element: '[data-tour="form-submit"]',
            title: 'Simpan pergerakan',
            description: 'Konfirmasi pergerakan stok setelah memastikan data sudah benar.',
            variants: ['special'],
        },
    ]),
    'cart-rules': buildTour('cart-rules', 'Aturan Keranjang'),
    'catalog-rules': buildTour('catalog-rules', 'Aturan Katalog'),
    'promotion-popups': buildTour('promotion-popups', 'Pop up Promosi'),
    configuration: buildTour('configuration', 'Konfigurasi', [
        {
            element: '[data-tour="configuration-grid"]',
            title: 'Kategori konfigurasi',
            description: 'Pilih kategori pengaturan toko seperti pembayaran, pengiriman, pajak, atau tampilan.',
            variants: ['index'],
        },
        {
            element: '[data-tour="configuration-fields"]',
            title: 'Pengaturan detail',
            description: 'Ubah nilai konfigurasi pada form ini lalu simpan perubahan.',
            variants: ['edit', 'special'],
        },
    ]),
    settings: {
        key: 'settings',
        steps: [
            ...commonSteps('settings', 'Profil'),
            {
                element: '[data-tour="settings-profile"]',
                title: 'Profil admin',
                description: 'Perbarui nama dan email akun admin Anda di sini.',
                variants: ['index'],
            },
            {
                element: '[data-tour="form-submit"]',
                title: 'Simpan profil',
                description: 'Simpan perubahan profil atau kata sandi setelah mengisi form.',
                variants: ['index'],
            },
        ],
    },
    roles: buildTour('roles', 'Peran'),
    staff: buildTour('staff', 'Staff'),
    'activity-logs': buildTour('activity-logs', 'Log Aktivitas'),
};

export function getTourStepsForVariant(
    tourKey: AdminTourKey,
    variant: TourPageVariant,
): AdminTourStep[] {
    const definition = adminTours[tourKey];
    if (!definition) {
        return [];
    }

    return definition.steps.filter((step) => {
        if (!step.variants || step.variants.length === 0) {
            return true;
        }

        return step.variants.includes(variant);
    });
}

export function filterExistingSteps(steps: AdminTourStep[]): AdminTourStep[] {
    return steps.filter((step) => document.querySelector(step.element));
}
