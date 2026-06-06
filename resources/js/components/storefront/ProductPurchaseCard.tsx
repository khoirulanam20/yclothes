import { Heart, Minus, Plus, Share2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatRupiah, cn } from '@/lib/utils';
import { guestToast } from '@/lib/guestToast';

type Variant = {
    id: number;
    sku: string;
    size?: string | null;
    color?: string | null;
    colorHex?: string | null;
    imageUrl?: string;
    finalPrice: number;
    stock: number;
    isPurchasable?: boolean;
    isOutOfStock?: boolean;
};

type Props = {
    productPrice: number;
    salePrice?: number | null;
    variants: Variant[];
    selectedVariantId: number | '';
    onVariantChange: (variantId: number) => void;
    qty: number;
    onQtyChange: (qty: number) => void;
    displayPrice: number;
    displayStock: number;
    displayPurchasable: boolean;
    displayOutOfStock: boolean;
    trackStock: boolean;
    processing: boolean;
    inWishlist: boolean;
    wishlistLoading: boolean;
    onAddToCart: () => void;
    onBuyNow: () => void;
    onToggleWishlist: () => void;
};

function variantLabel(variant: Variant): string {
    return [variant.size, variant.color].filter(Boolean).join(' / ') || variant.sku;
}

export function ProductPurchaseCard({
    productPrice,
    salePrice,
    variants,
    selectedVariantId,
    onVariantChange,
    qty,
    onQtyChange,
    displayPrice,
    displayStock,
    displayPurchasable,
    displayOutOfStock,
    trackStock,
    processing,
    inWishlist,
    wishlistLoading,
    onAddToCart,
    onBuyNow,
    onToggleWishlist,
}: Props) {
    const showStrike = salePrice && displayPrice < productPrice;
    const subtotal = displayPrice * qty;

    const handleShare = async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            guestToast.success('Link produk disalin.');
        } catch {
            guestToast.error('Gagal menyalin link.');
        }
    };

    return (
        <div className="store-card overflow-hidden rounded-xl border border-border/60 bg-card shadow-[0_4px_24px_rgba(15,23,42,0.08)]">
            <div className="border-b bg-primary/10 px-4 py-3 text-sm font-semibold text-primary">
                Atur jumlah dan varian
            </div>

            <div className="space-y-4 p-4">
                {variants.length > 0 && (
                    <div className="space-y-2">
                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Varian</p>
                        <div className="flex flex-wrap gap-2">
                            {variants.map((variant) => {
                                const active = variant.id === Number(selectedVariantId);
                                const disabled = variant.isOutOfStock;

                                return (
                                    <button
                                        key={variant.id}
                                        type="button"
                                        disabled={disabled}
                                        onClick={() => onVariantChange(variant.id)}
                                        className={cn(
                                            'rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
                                            active
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input bg-background hover:border-primary/50',
                                            disabled && 'cursor-not-allowed opacity-50 line-through',
                                        )}
                                    >
                                        {variant.colorHex && (
                                            <span
                                                className="mr-1.5 inline-block size-2.5 rounded-full border align-middle"
                                                style={{ backgroundColor: variant.colorHex }}
                                            />
                                        )}
                                        {variantLabel(variant)}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}

                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center rounded-lg border bg-background">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-9 rounded-none"
                            onClick={() => onQtyChange(Math.max(1, qty - 1))}
                            disabled={qty <= 1}
                        >
                            <Minus className="size-4" />
                        </Button>
                        <span className="w-10 text-center text-sm font-medium">{qty}</span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-9 rounded-none"
                            onClick={() => onQtyChange(qty + 1)}
                            disabled={trackStock && displayStock > 0 && qty >= displayStock}
                        >
                            <Plus className="size-4" />
                        </Button>
                    </div>
                    <p className="text-right text-sm text-muted-foreground">
                        Stok Total:{' '}
                        <span
                            className={cn(
                                'font-semibold',
                                displayOutOfStock && 'text-destructive',
                                !displayOutOfStock && trackStock && displayStock <= 5 && 'text-amber-600',
                                !displayOutOfStock && !(trackStock && displayStock <= 5) && 'text-foreground',
                            )}
                        >
                            {displayOutOfStock
                                ? 'Habis'
                                : trackStock
                                  ? `Sisa ${displayStock}`
                                  : 'Tersedia'}
                        </span>
                    </p>
                </div>

                <div className="flex items-end justify-between gap-3 border-t pt-4">
                    <span className="text-sm text-muted-foreground">Subtotal</span>
                    <div className="text-right">
                        {showStrike && (
                            <p className="text-xs text-muted-foreground line-through">
                                {formatRupiah(productPrice * qty)}
                            </p>
                        )}
                        <p className="text-xl font-bold leading-tight">{formatRupiah(subtotal)}</p>
                    </div>
                </div>

                <div className="space-y-2">
                    <Button
                        className="h-11 w-full text-base font-semibold"
                        onClick={onAddToCart}
                        disabled={processing || !displayPurchasable}
                    >
                        {displayPurchasable ? '+ Keranjang' : 'Stok Habis'}
                    </Button>
                    <Button
                        variant="outline"
                        className="h-11 w-full border-primary bg-background text-base font-semibold text-primary hover:bg-primary hover:text-primary-foreground"
                        onClick={onBuyNow}
                        disabled={processing || !displayPurchasable}
                    >
                        Beli Langsung
                    </Button>
                </div>

                <div className="flex items-center justify-around border-t pt-4">
                    <button
                        type="button"
                        onClick={onToggleWishlist}
                        disabled={wishlistLoading}
                        className="flex flex-col items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-primary"
                    >
                        <Heart className={cn('size-5', inWishlist && 'fill-primary text-primary')} />
                        Wishlist
                    </button>
                    <button
                        type="button"
                        onClick={handleShare}
                        className="flex flex-col items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-primary"
                    >
                        <Share2 className="size-5" />
                        Bagikan
                    </button>
                </div>
            </div>
        </div>
    );
}
