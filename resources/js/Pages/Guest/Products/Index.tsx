import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { SlidersHorizontal } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';
import { type ProductCardData } from '@/components/ProductCard';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { ProductFilterSidebar } from '@/components/storefront/ProductFilterSidebar';
import { ProductFilterMobileSheet } from '@/components/storefront/ProductFilterMobileSheet';
import { hasActiveProductFilters } from '@/components/storefront/ProductFilterPanel';
import { ProductGrid } from '@/components/storefront/ProductGrid';
import { ProductSortDropdown } from '@/components/storefront/ProductSortDropdown';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
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
        featured?: string | null;
        on_sale?: string | null;
        badge?: string | null;
        badge_label?: string | null;
    };
    activeCategory?: ActiveCategory | null;
    pageTitle?: string | null;
};

const sortOptions = [
    { value: '', label: 'Paling Sesuai' },
    { value: 'price_asc', label: 'Harga Terendah' },
    { value: 'price_desc', label: 'Harga Tertinggi' },
];

export default function Index({ products, categories, filters, activeCategory, pageTitle }: Props) {
    const [filterOpen, setFilterOpen] = useState(false);
    const isFlashSale = filters.flash_sale === '1';
    const hasActiveFilters = hasActiveProductFilters(filters);

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
            <Head title={pageTitle ?? (isFlashSale ? 'Flash Sale' : activeCategory ? `${activeCategory.name} — Produk` : 'Produk')} />
            <PageContainer>
                <Breadcrumb items={breadcrumbItems} />

                <div className="flex flex-col items-start gap-4 lg:flex-row">
                    <ProductFilterSidebar categories={categories} filters={filters} />

                    <div className="min-w-0 flex-1">
                        <SectionCard noPadding>
                            <div className="space-y-3 border-b px-4 py-3 lg:flex lg:items-center lg:justify-between lg:space-y-0">
                                <p className="text-sm text-muted-foreground">
                                    Menampilkan{' '}
                                    <span className="font-medium text-foreground">{products.meta.total}</span> produk
                                    {pageTitle ? ` di ${pageTitle}` : isFlashSale ? ' di Flash Sale' : activeCategory ? ` di ${activeCategory.name}` : ''}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="relative h-9 shrink-0 gap-1.5 lg:hidden"
                                        onClick={() => setFilterOpen(true)}
                                    >
                                        <SlidersHorizontal className="size-4" />
                                        Filter
                                        {hasActiveFilters && (
                                            <span className="absolute -right-0.5 -top-0.5 size-2 rounded-full bg-primary" aria-hidden />
                                        )}
                                    </Button>
                                    <ProductSortDropdown
                                        value={filters.sort ?? ''}
                                        options={sortOptions}
                                        onChange={applySort}
                                        className="min-w-0 flex-1 justify-end sm:flex-initial sm:justify-start"
                                    />
                                </div>
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

            <ProductFilterMobileSheet
                open={filterOpen}
                onOpenChange={setFilterOpen}
                categories={categories}
                filters={filters}
            />
        </GuestLayout>
    );
}
