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
    productName: string;
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
    variantPreviewUrl?: string;
};

function variantLabel(variant: Variant): string {
    return [variant.size, variant.color].filter(Boolean).join(' / ') || variant.sku;
}

export function ProductPurchaseCard({
    productName,
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
    variantPreviewUrl,
}: Props) {
    const selectedVariant = variants.find((v) => v.id === Number(selectedVariantId));
    const showStrike = salePrice && displayPrice < productPrice;

    const handleShare = async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            guestToast.success('Link produk disalin.');
        } catch {
            guestToast.error('Gagal menyalin link.');
        }
    };

    return (
        <div className="store-card rounded-xl border bg-card p-4 lg:sticky lg:top-24">
            <div className="mb-4 rounded-lg bg-primary/10 px-3 py-2 text-xs font-medium text-primary">
                Atur jumlah dan varian
            </div>

            {selectedVariant && (
                <div className="mb-4 flex items-center gap-3 rounded-lg border bg-muted/30 p-2">
                    {(variantPreviewUrl || selectedVariant.imageUrl) && (
                        <img
                            src={variantPreviewUrl || selectedVariant.imageUrl}
                            alt=""
                            className="size-12 rounded-md object-cover"
                        />
                    )}
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium">{productName}</p>
                        <p className="text-xs text-muted-foreground">{variantLabel(selectedVariant)}</p>
                    </div>
                </div>
            )}

            {variants.length > 0 && (
                <div className="mb-4 space-y-2">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Varian</p>
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

            <div className="mb-4 flex items-center justify-between gap-3">
                <div className="flex items-center rounded-lg border">
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
                <p className="text-sm text-muted-foreground">
                    Stok:{' '}
                    <span className={cn('font-medium', displayOutOfStock && 'text-destructive')}>
                        {trackStock ? displayStock : 'Tersedia'}
                    </span>
                </p>
            </div>

            <div className="mb-4 space-y-1">
                <p className="text-xs text-muted-foreground">Subtotal</p>
                {showStrike && (
                    <p className="text-sm text-muted-foreground line-through">{formatRupiah(productPrice)}</p>
                )}
                <p className="text-2xl font-bold">{formatRupiah(displayPrice * qty)}</p>
            </div>

            <div className="space-y-2">
                <Button
                    className="w-full"
                    onClick={onAddToCart}
                    disabled={processing || !displayPurchasable}
                >
                    {displayPurchasable ? '+ Keranjang' : 'Stok Habis'}
                </Button>
                <Button
                    variant="outline"
                    className="w-full border-primary text-primary hover:bg-primary/5"
                    onClick={onBuyNow}
                    disabled={processing || !displayPurchasable}
                >
                    Beli Langsung
                </Button>
            </div>

            <div className="mt-4 flex items-center justify-center gap-4 border-t pt-4">
                <button
                    type="button"
                    onClick={onToggleWishlist}
                    disabled={wishlistLoading}
                    className="flex items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-primary"
                >
                    <Heart className={cn('size-4', inWishlist && 'fill-primary text-primary')} />
                    Wishlist
                </button>
                <button
                    type="button"
                    onClick={handleShare}
                    className="flex items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-primary"
                >
                    <Share2 className="size-4" />
                    Bagikan
                </button>
            </div>
        </div>
    );
}
