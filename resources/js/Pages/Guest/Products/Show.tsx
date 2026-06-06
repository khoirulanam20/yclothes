import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Star } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';
import { type ProductCardData } from '@/components/ProductCard';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { ProductDetailTabs } from '@/components/storefront/ProductDetailTabs';
import { ProductGallery } from '@/components/storefront/ProductGallery';
import { ProductPurchaseCard } from '@/components/storefront/ProductPurchaseCard';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Badge } from '@/components/ui/badge';
import { toggleWishlist } from '@/lib/toggleWishlist';
import { guestToast } from '@/lib/guestToast';
import { contrastTextColor } from '@/lib/utils';

type Variant = {
    id: number;
    sku: string;
    price: number;
    finalPrice: number;
    imageUrl?: string;
    imagesUrl?: string[];
    ownImagesUrl?: string[];
    size?: string | null;
    color?: string | null;
    colorHex?: string | null;
    stock: number;
    trackStock?: boolean;
    isPurchasable?: boolean;
    isOutOfStock?: boolean;
};

type Review = {
    id: number;
    rating: number;
    comment: string;
    customerName: string;
    createdAt?: string;
    imagesUrl?: string[];
};

type Product = ProductCardData & {
    description?: string | null;
    imagesUrl?: string[];
    sizes?: string[];
    colors?: string[];
    ratingAvg?: number;
    reviewCount?: number;
    trackStock?: boolean;
    variants?: Variant[];
    metaTitle?: string | null;
    metaDescription?: string | null;
};

type BreadcrumbItem = { label: string; href: string };

type Props = {
    product: Product;
    relatedProducts: ProductCardData[];
    upSellProducts?: ProductCardData[];
    reviews: Review[];
    inWishlist: boolean;
    productStock: number;
    variants: Variant[];
    isPurchasable?: boolean;
    isOutOfStock?: boolean;
    categoryPath?: BreadcrumbItem[];
};

function resolveDisplayImages(variant: Variant | undefined, product: Product): string[] {
    if (variant?.ownImagesUrl?.length) {
        return variant.ownImagesUrl;
    }

    if (variant?.imagesUrl?.length) {
        return variant.imagesUrl;
    }

    if (variant?.imageUrl) {
        return [variant.imageUrl];
    }

    if (product.imagesUrl?.length) {
        return product.imagesUrl;
    }

    return product.imageUrl ? [product.imageUrl] : [];
}

function variantOverlayLabel(variant: Variant | undefined): string | null {
    if (!variant) {
        return null;
    }

    return [variant.size, variant.color].filter(Boolean).join(' / ') || null;
}

export default function Show({
    product,
    relatedProducts,
    upSellProducts = [],
    reviews,
    inWishlist: initialInWishlist,
    productStock,
    variants,
    isPurchasable: defaultPurchasable = true,
    isOutOfStock: defaultOutOfStock = false,
    categoryPath = [],
}: Props) {
    const [activeImage, setActiveImage] = useState('');
    const [inWishlist, setInWishlist] = useState(initialInWishlist);
    const [wishlistLoading, setWishlistLoading] = useState(false);

    const { data, setData, post, processing } = useForm({
        product_id: product.id,
        variant_id: variants[0]?.id ?? '',
        qty: 1,
        size: '',
        color: '',
    });

    const selectedVariant = useMemo(
        () => variants.find((variant) => variant.id === Number(data.variant_id)),
        [variants, data.variant_id],
    );

    const displayImages = useMemo(
        () => resolveDisplayImages(selectedVariant, product),
        [selectedVariant, product],
    );

    const displayStock = selectedVariant?.stock ?? productStock;
    const displayPrice = selectedVariant?.finalPrice ?? product.finalPrice;
    const displayPurchasable = selectedVariant?.isPurchasable ?? defaultPurchasable;
    const displayOutOfStock = selectedVariant?.isOutOfStock ?? defaultOutOfStock;
    const trackStock = selectedVariant?.trackStock ?? product.trackStock ?? true;

    useEffect(() => {
        if (displayImages[0]) {
            setActiveImage(displayImages[0]);
        }
    }, [displayImages]);

    useEffect(() => {
        if (trackStock && displayStock > 0 && data.qty > displayStock) {
            setData('qty', displayStock);
        }
    }, [displayStock, data.qty, setData, trackStock]);

    const handleVariantChange = (variantId: number) => {
        setData((current) => ({
            ...current,
            variant_id: variantId,
            qty: 1,
        }));
    };

    const addToCart = () => post('/cart/add', { preserveScroll: true });

    const buyNow = () => {
        router.post('/cart/add', { ...data, buy_now: true });
    };

    const handleToggleWishlist = async () => {
        if (wishlistLoading) {
            return;
        }

        setWishlistLoading(true);

        try {
            const result = await toggleWishlist(product.id);
            setInWishlist(result.in_wishlist);
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
        <GuestLayout>
            <Head title={product.metaTitle ?? product.name}>
                {product.metaDescription && (
                    <meta name="description" content={product.metaDescription} />
                )}
            </Head>
            <PageContainer>
                <Breadcrumb
                    items={[
                        { label: 'Beranda', href: '/' },
                        { label: 'Produk', href: '/products' },
                        ...categoryPath,
                        { label: product.name },
                    ]}
                />

                <SectionCard noPadding className="mb-4 overflow-hidden">
                    <div className="grid gap-0 lg:grid-cols-12">
                        <div className="p-4 lg:col-span-4">
                            <ProductGallery
                                images={displayImages}
                                activeImage={activeImage}
                                onActiveChange={setActiveImage}
                                overlayLabel={variantOverlayLabel(selectedVariant)}
                            />
                        </div>

                        <div className="border-t p-4 lg:col-span-5 lg:border-t-0 lg:border-l">
                            {product.badge && (
                                <Badge
                                    className="mb-2 border-transparent"
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
                            <h1 className="text-xl font-bold leading-snug lg:text-2xl">{product.name}</h1>
                            {product.ratingAvg ? (
                                <div className="mt-2 flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Star className="size-4 fill-amber-400 text-amber-400" />
                                    <span className="font-medium text-foreground">{product.ratingAvg.toFixed(1)}</span>
                                    <span>· {product.reviewCount} ulasan</span>
                                </div>
                            ) : null}

                            <div className="mt-6">
                                <ProductDetailTabs
                                    description={product.description}
                                    ratingAvg={product.ratingAvg}
                                    reviewCount={product.reviewCount}
                                    reviews={reviews}
                                    relatedProducts={relatedProducts}
                                    upSellProducts={upSellProducts}
                                />
                            </div>
                        </div>

                        <div className="border-t p-4 lg:col-span-3 lg:border-t-0 lg:border-l lg:bg-muted/20">
                            <ProductPurchaseCard
                                productName={product.name}
                                productPrice={product.price}
                                salePrice={product.salePrice}
                                variants={variants}
                                selectedVariantId={data.variant_id}
                                onVariantChange={handleVariantChange}
                                qty={data.qty}
                                onQtyChange={(qty) => setData('qty', qty)}
                                displayPrice={displayPrice}
                                displayStock={displayStock}
                                displayPurchasable={displayPurchasable}
                                displayOutOfStock={displayOutOfStock}
                                trackStock={trackStock}
                                processing={processing}
                                inWishlist={inWishlist}
                                wishlistLoading={wishlistLoading}
                                onAddToCart={addToCart}
                                onBuyNow={buyNow}
                                onToggleWishlist={handleToggleWishlist}
                                variantPreviewUrl={activeImage || displayImages[0]}
                            />
                        </div>
                    </div>
                </SectionCard>
            </PageContainer>
        </GuestLayout>
    );
}
