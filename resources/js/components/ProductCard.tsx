import { Link } from '@inertiajs/react';
import { Heart, Star } from 'lucide-react';
import { useState } from 'react';
import { formatRupiah, contrastTextColor, cn } from '@/lib/utils';
import { toggleWishlist } from '@/lib/toggleWishlist';
import { guestToast } from '@/lib/guestToast';

export type ProductCardData = {
    id: number;
    name: string;
    slug: string;
    imageUrl: string;
    finalPrice: number;
    price: number;
    salePrice?: number | null;
    badge?: string | null;
    badgeColor?: string | null;
    discountPercentage?: number | null;
    catalogUnitPrice?: number;
    catalogHasDiscount?: boolean;
    ratingAvg?: number;
    reviewCount?: number;
    isOutOfStock?: boolean;
    isPurchasable?: boolean;
    inWishlist?: boolean;
};

type Props = {
    product: ProductCardData;
    compact?: boolean;
    /** Tampilkan tombol wishlist (hanya halaman katalog produk & wishlist) */
    showWishlist?: boolean;
    /** Semua item di halaman wishlist — icon love terisi saat awal */
    wishlistMode?: boolean;
    onWishlistToggle?: (productId: number, inWishlist: boolean) => void;
};

function DiscountBadge({ percentage }: { percentage: number }) {
    return (
        <span className="absolute left-0 top-2 z-10 inline-flex items-center rounded-r-md bg-[#ee4d2d] px-2 py-0.5 text-[10px] font-bold leading-none text-white shadow-sm">
            {percentage}%
        </span>
    );
}

export function ProductCard({ product, compact, showWishlist = false, wishlistMode, onWishlistToggle }: Props) {
    const displayPrice = product.catalogUnitPrice ?? product.finalPrice;
    const hasSale = product.salePrice || product.catalogHasDiscount;
    const showStrikePrice = hasSale && displayPrice < product.price;
    const hasRating = product.ratingAvg != null && product.ratingAvg > 0;

    const [inWishlist, setInWishlist] = useState(
        wishlistMode || product.inWishlist || false,
    );
    const [wishlistLoading, setWishlistLoading] = useState(false);

    const handleWishlistClick = async (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (wishlistLoading) {
            return;
        }

        setWishlistLoading(true);

        try {
            const result = await toggleWishlist(product.id);
            setInWishlist(result.in_wishlist);
            onWishlistToggle?.(product.id, result.in_wishlist);
            guestToast.success(
                result.in_wishlist ? 'Ditambahkan ke wishlist.' : 'Dihapus dari wishlist.',
            );
        } catch (error) {
            guestToast.error(error instanceof Error ? error.message : 'Gagal memperbarui wishlist.');
        } finally {
            setWishlistLoading(false);
        }
    };

    return (
        <div
            className={cn(
                'group relative flex h-full flex-col overflow-hidden rounded-lg border border-border/60 bg-card store-card store-card-hover',
                compact && 'min-w-[140px]',
            )}
        >
            <Link
                href={`/products/${product.slug}`}
                className="flex min-h-0 flex-1 flex-col focus:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-lg"
            >
                <div className="relative store-image-zoom aspect-square shrink-0 overflow-hidden rounded-t-lg bg-muted">
                    {product.discountPercentage ? (
                        <DiscountBadge percentage={product.discountPercentage} />
                    ) : null}
                    {product.badge && (
                        <div
                            className="absolute bottom-2 left-2 z-10 max-w-[calc(100%-2.5rem)] truncate rounded px-1.5 py-0.5 text-[10px] font-semibold text-white shadow-sm"
                            style={{
                                backgroundColor: product.badgeColor ?? '#16a34a',
                                color: contrastTextColor(product.badgeColor ?? '#16a34a'),
                            }}
                        >
                            {product.badge}
                        </div>
                    )}
                    {product.isOutOfStock && (
                        <div className="absolute inset-x-0 bottom-0 z-10 bg-foreground/70 px-2 py-1.5 text-center">
                            <span className="text-[11px] font-semibold uppercase tracking-wide text-background">
                                Stok Habis
                            </span>
                        </div>
                    )}
                    <img
                        src={product.imageUrl}
                        alt={product.name}
                        loading="lazy"
                        className={cn(
                            'h-full w-full object-cover',
                            product.isOutOfStock && 'opacity-75 grayscale-[30%]',
                        )}
                    />
                </div>

                <div className={cn('flex flex-1 flex-col bg-card p-2.5', !compact && 'p-3')}>
                    <h3 className="line-clamp-2 min-h-[2.5rem] text-sm leading-snug text-foreground/90 group-hover:text-foreground">
                        {product.name}
                    </h3>

                    <div className="mt-1 min-h-[2.5rem]">
                        <span className="block text-base font-bold leading-tight text-foreground">
                            {formatRupiah(displayPrice)}
                        </span>
                        {showStrikePrice ? (
                            <span className="block text-xs text-muted-foreground line-through">
                                {formatRupiah(product.price)}
                            </span>
                        ) : (
                            <span className="block text-xs text-transparent select-none" aria-hidden>
                                —
                            </span>
                        )}
                    </div>

                    <div className="mt-auto min-h-[1.125rem] pt-1 text-xs text-muted-foreground">
                        {hasRating ? (
                            <div className="flex items-center gap-1">
                                <Star className="size-3 fill-amber-400 text-amber-400" />
                                <span className="font-medium text-foreground/80">
                                    {product.ratingAvg!.toFixed(1)}
                                </span>
                                {product.reviewCount ? (
                                    <span>· {product.reviewCount} ulasan</span>
                                ) : null}
                            </div>
                        ) : (
                            <span className="invisible" aria-hidden>—</span>
                        )}
                    </div>
                </div>
            </Link>

            {showWishlist ? (
                <button
                    type="button"
                    onClick={handleWishlistClick}
                    disabled={wishlistLoading}
                    className={cn(
                        'absolute right-2 top-2 z-20 flex size-8 items-center justify-center rounded-full',
                        'bg-background/90 shadow-sm backdrop-blur-sm transition-colors',
                        'hover:bg-background disabled:opacity-60',
                    )}
                    aria-label={inWishlist ? 'Hapus dari wishlist' : 'Tambah ke wishlist'}
                >
                    <Heart
                        className={cn(
                            'size-4 transition-colors',
                            inWishlist ? 'fill-red-500 text-red-500' : 'text-muted-foreground',
                        )}
                    />
                </button>
            ) : null}
        </div>
    );
}
