import { MobileBottomSheet } from '@/components/storefront/MobileBottomSheet';
import { ProductFilterPanel, type ProductFilters } from '@/components/storefront/ProductFilterPanel';
import type { CategoryNav } from '@/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    categories: CategoryNav[];
    filters: ProductFilters;
};

export function ProductFilterMobileSheet({ open, onOpenChange, categories, filters }: Props) {
    const close = () => onOpenChange(false);

    return (
        <MobileBottomSheet open={open} onOpenChange={onOpenChange} title="Filter">
            <ProductFilterPanel
                categories={categories}
                filters={filters}
                showHeader={false}
                onApplied={close}
                onNavigate={close}
            />
        </MobileBottomSheet>
    );
}
