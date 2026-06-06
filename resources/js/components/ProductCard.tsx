import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { formatRupiah } from '@/lib/utils';
import { contrastTextColor, cn } from '@/lib/utils';

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
};

export function ProductCard({ product, compact }: { product: ProductCardData; compact?: boolean }) {
    const displayPrice = product.catalogUnitPrice ?? product.finalPrice;
    const hasSale = product.salePrice || product.catalogHasDiscount;

    return (
        <Link
            href={`/products/${product.slug}`}
            className={cn(
                'block bg-card rounded-lg border shadow-sm overflow-hidden group hover:shadow-md transition-shadow',
                compact && 'min-w-[140px]',
            )}
        >
            <div className="relative aspect-square overflow-hidden bg-muted">
                {product.badge && (
                    <Badge
                        className="absolute left-2 top-2 z-10 text-[10px] px-1.5 py-0 border-transparent"
                        style={
                            product.badgeColor
                                ? {
                                      backgroundColor: product.badgeColor,
                                      color: contrastTextColor(product.badgeColor),
                                  }
                                : undefined
                        }
                    >
                        {product.badge}
                    </Badge>
                )}
                {product.discountPercentage ? (
                    <Badge className="bg-destructive text-destructive-foreground border-transparent absolute right-2 top-2 z-10 text-[10px] px-1.5 py-0">
                        -{product.discountPercentage}%
                    </Badge>
                ) : null}
                {product.isOutOfStock && (
                    <Badge className="absolute bottom-2 left-2 z-10 text-[10px] px-1.5 py-0 bg-muted text-muted-foreground border-transparent">
                        Habis
                    </Badge>
                )}
                <img
                    src={product.imageUrl}
                    alt={product.name}
                    className="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
            </div>
            <div className={cn('p-2.5', !compact && 'p-3')}>
                <h3 className="text-sm leading-snug line-clamp-2 min-h-[2.5rem]">{product.name}</h3>
                <div className="mt-1.5">
                    <span className="font-bold text-primary text-sm">{formatRupiah(displayPrice)}</span>
                    {hasSale && displayPrice < product.price && (
                        <span className="block text-xs text-muted-foreground line-through">
                            {formatRupiah(product.price)}
                        </span>
                    )}
                </div>
                {product.ratingAvg != null && product.ratingAvg > 0 && (
                    <div className="mt-1 text-xs text-muted-foreground">
                        ★ {product.ratingAvg.toFixed(1)}
                        {product.reviewCount ? ` (${product.reviewCount})` : ''}
                    </div>
                )}
            </div>
        </Link>
    );
}
