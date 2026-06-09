import { ProductFilterPanel, type ProductFilters } from '@/components/storefront/ProductFilterPanel';
import type { CategoryNav } from '@/types';

type Props = {
    categories: CategoryNav[];
    filters: ProductFilters;
};

export function ProductFilterSidebar({ categories, filters }: Props) {
    return (
        <aside className="hidden w-full shrink-0 lg:block lg:sticky lg:top-20 lg:z-30 lg:w-64 lg:self-start">
            <div className="max-h-[calc(100vh-5.5rem)] overflow-y-auto rounded-lg border bg-card shadow-sm">
                <div className="border-b bg-muted/60 px-4 py-3">
                    <h2 className="text-sm font-bold text-foreground">Filter</h2>
                </div>
                <ProductFilterPanel categories={categories} filters={filters} showHeader={false} />
            </div>
        </aside>
    );
}
