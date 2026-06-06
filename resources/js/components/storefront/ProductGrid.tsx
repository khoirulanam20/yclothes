import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { cn } from '@/lib/utils';

type Props = {
    products: ProductCardData[];
    layout?: 'grid' | 'scroll';
    compact?: boolean;
    columns?: 'default' | 'wide';
    className?: string;
    showWishlist?: boolean;
    wishlistMode?: boolean;
    onWishlistToggle?: (productId: number, inWishlist: boolean) => void;
};

export function ProductGrid({
    products,
    layout = 'grid',
    compact = false,
    columns = 'default',
    className,
    showWishlist = false,
    wishlistMode = false,
    onWishlistToggle,
}: Props) {
    if (products.length === 0) {
        return null;
    }

    if (layout === 'scroll') {
        return (
            <div
                className={cn(
                    'store-scroll-x flex gap-3 overflow-x-auto pb-1 -mx-1 px-1',
                    className,
                )}
            >
                {products.map((product) => (
                    <div
                        key={product.id}
                        className={cn('shrink-0 self-stretch', compact ? 'w-[160px]' : 'w-[180px]')}
                    >
                        <ProductCard
                            product={product}
                            compact
                            showWishlist={showWishlist}
                            wishlistMode={wishlistMode}
                            onWishlistToggle={onWishlistToggle}
                        />
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div
            className={cn(
                'grid auto-rows-fr gap-3',
                columns === 'wide'
                    ? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5'
                    : 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
                className,
            )}
        >
            {products.map((product) => (
                <ProductCard
                    key={product.id}
                    product={product}
                    compact={compact}
                    showWishlist={showWishlist}
                    wishlistMode={wishlistMode}
                    onWishlistToggle={onWishlistToggle}
                />
            ))}
        </div>
    );
}
