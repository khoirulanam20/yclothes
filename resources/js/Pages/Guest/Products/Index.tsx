import { Head, router } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { type ProductCardData } from '@/components/ProductCard';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { ProductFilterSidebar } from '@/components/storefront/ProductFilterSidebar';
import { ProductGrid } from '@/components/storefront/ProductGrid';
import { ProductSortDropdown } from '@/components/storefront/ProductSortDropdown';
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
    { value: '', label: 'Paling Sesuai' },
    { value: 'price_asc', label: 'Harga Terendah' },
    { value: 'price_desc', label: 'Harga Tertinggi' },
];

export default function Index({ products, categories, filters, activeCategory }: Props) {
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
                            <div className="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
                                <p className="text-sm text-muted-foreground">
                                    Menampilkan {products.meta.total} produk
                                    {isFlashSale ? ' di Flash Sale' : activeCategory ? ` di ${activeCategory.name}` : ''}
                                </p>
                                <ProductSortDropdown
                                    value={filters.sort ?? ''}
                                    options={sortOptions}
                                    onChange={applySort}
                                />
                            </div>
                            <div className="p-4">
                                {products.data.length > 0 ? (
                                    <ProductGrid products={products.data} columns="wide" showWishlist />
                                ) : (
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
