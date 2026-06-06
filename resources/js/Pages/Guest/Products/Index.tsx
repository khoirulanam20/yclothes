import { Head, router } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { ProductFilterSidebar } from '@/components/storefront/ProductFilterSidebar';
import { SectionCard } from '@/components/storefront/SectionCard';
import type { CategoryNav } from '@/types';

type BreadcrumbItem = { label: string; href: string };
type ActiveCategory = { name: string; slug: string; breadcrumbPath: BreadcrumbItem[] };

type Props = {
    products: Paginated<ProductCardData>;
    categories: CategoryNav[];
    filters: {
        search?: string;
        category?: string;
        sort?: string;
        min_price?: string;
        max_price?: string;
        flash_sale?: string | null;
    };
    activeCategory?: ActiveCategory | null;
    pageTitle?: string | null;
};

const sortOptions = [
    { value: '', label: 'Terbaru' },
    { value: 'price_asc', label: 'Harga Terendah' },
    { value: 'price_desc', label: 'Harga Tertinggi' },
];

export default function Index({ products, categories, filters, activeCategory, pageTitle }: Props) {
    const isFlashSale = filters.flash_sale === '1';

    const applySort = (sort: string) => {
        router.get('/products', { ...filters, sort }, { preserveState: true });
    };

    const breadcrumbItems = [
        { label: 'Beranda', href: '/' },
        ...(isFlashSale
            ? [{ label: 'Flash Sale', href: '/products?flash_sale=1' }]
            : [{ label: 'Produk', href: '/products' }, ...(activeCategory?.breadcrumbPath ?? [])]),
    ];

    return (
        <GuestLayout>
            <Head title={isFlashSale ? 'Flash Sale' : activeCategory ? `${activeCategory.name} — Produk` : 'Produk'} />
            <PageContainer>
                <Breadcrumb items={breadcrumbItems} />

                <div className="flex flex-col items-start gap-4 lg:flex-row">
                    <ProductFilterSidebar categories={categories} filters={filters} />

                    <div className="min-w-0 flex-1">
                        <SectionCard noPadding>
                            <div className="flex items-center justify-between gap-3 border-b px-4 py-3">
                                <p className="text-sm text-muted-foreground">
                                    {products.meta.total} produk ditemukan
                                    {isFlashSale ? ' di Flash Sale' : activeCategory ? ` di ${activeCategory.name}` : ''}
                                </p>
                                <div className="flex gap-1 overflow-x-auto">
                                    {sortOptions.map((opt) => (
                                        <button
                                            key={opt.value}
                                            type="button"
                                            onClick={() => applySort(opt.value)}
                                            className={`whitespace-nowrap rounded-full px-3 py-1.5 text-xs transition-colors ${
                                                (filters.sort ?? '') === opt.value
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted hover:bg-muted/80'
                                            }`}
                                        >
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="p-4">
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                                    {products.data.map((p) => (
                                        <ProductCard key={p.id} product={p} />
                                    ))}
                                </div>
                                {products.data.length === 0 && (
                                    <p className="py-12 text-center text-muted-foreground">
                                        Produk tidak ditemukan.
                                    </p>
                                )}
                                <PaginationLinks pagination={products} />
                            </div>
                        </SectionCard>
                    </div>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
