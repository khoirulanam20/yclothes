import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
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
            <ProductScrollRow
                products={products}
                compact={compact}
                className={className}
                showWishlist={showWishlist}
                wishlistMode={wishlistMode}
                onWishlistToggle={onWishlistToggle}
            />
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

function ProductScrollRow({
    products,
    compact,
    className,
    showWishlist,
    wishlistMode,
    onWishlistToggle,
}: {
    products: ProductCardData[];
    compact?: boolean;
    className?: string;
    showWishlist?: boolean;
    wishlistMode?: boolean;
    onWishlistToggle?: (productId: number, inWishlist: boolean) => void;
}) {
    const scrollRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const updateScrollState = useCallback(() => {
        const el = scrollRef.current;
        if (!el) {
            return;
        }

        setCanScrollLeft(el.scrollLeft > 0);
        setCanScrollRight(el.scrollLeft + el.clientWidth < el.scrollWidth - 1);
    }, []);

    useEffect(() => {
        updateScrollState();
        const el = scrollRef.current;
        if (!el) {
            return;
        }

        el.addEventListener('scroll', updateScrollState, { passive: true });
        const observer = new ResizeObserver(updateScrollState);
        observer.observe(el);

        return () => {
            el.removeEventListener('scroll', updateScrollState);
            observer.disconnect();
        };
    }, [products, updateScrollState]);

    const scroll = (direction: 'left' | 'right') => {
        const el = scrollRef.current;
        if (!el) {
            return;
        }

        const cardWidth = compact ? 160 : 180;
        const amount = cardWidth * 2 + 12;

        el.scrollBy({
            left: direction === 'left' ? -amount : amount,
            behavior: 'smooth',
        });
    };

    const showNav = canScrollLeft || canScrollRight;

    return (
        <div className={cn('relative', className)}>
            {showNav && canScrollLeft && (
                <button
                    type="button"
                    onClick={() => scroll('left')}
                    className="absolute left-0 top-1/2 z-10 hidden -translate-y-1/2 rounded-full border border-border/60 bg-background/95 p-2 shadow-md backdrop-blur-sm transition-opacity hover:bg-background sm:flex"
                    aria-label="Produk sebelumnya"
                >
                    <ChevronLeft className="size-5" />
                </button>
            )}
            {showNav && canScrollRight && (
                <button
                    type="button"
                    onClick={() => scroll('right')}
                    className="absolute right-0 top-1/2 z-10 hidden -translate-y-1/2 rounded-full border border-border/60 bg-background/95 p-2 shadow-md backdrop-blur-sm transition-opacity hover:bg-background sm:flex"
                    aria-label="Produk berikutnya"
                >
                    <ChevronRight className="size-5" />
                </button>
            )}
            <div
                ref={scrollRef}
                className={cn(
                    'store-scroll-x flex gap-3 overflow-x-auto pb-1',
                    showNav && 'sm:px-10',
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
        </div>
    );
}
