import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { toggleWishlist } from '@/lib/toggleWishlist';
import { guestToast } from '@/lib/guestToast';
import { cn, contrastTextColor, formatRupiah } from '@/lib/utils';

type Variant = {
    id: number; sku: string; price: number; imageUrl?: string; size?: string | null;
    color?: string | null; colorHex?: string | null; stock: number; trackStock?: boolean;
};
type Review = { id: number; rating: number; comment: string; customerName: string; createdAt?: string };
type Product = ProductCardData & {
    description?: string | null; imagesUrl?: string[]; sizes?: string[]; colors?: string[];
    ratingAvg?: number; reviewCount?: number; trackStock?: boolean; variants?: Variant[];
    metaTitle?: string | null; metaDescription?: string | null;
};
type BreadcrumbItem = { label: string; href: string };
type Props = {
    product: Product; relatedProducts: ProductCardData[]; reviews: Review[];
    inWishlist: boolean; productStock: number; variants: Variant[];
    categoryPath?: BreadcrumbItem[];
};

export default function Show({ product, relatedProducts, reviews, inWishlist: initialInWishlist, productStock, variants, categoryPath = [] }: Props) {
    const images = product.imagesUrl?.length ? product.imagesUrl : [product.imageUrl];
    const [activeImage, setActiveImage] = useState(images[0]);
    const [inWishlist, setInWishlist] = useState(initialInWishlist);
    const [wishlistLoading, setWishlistLoading] = useState(false);

    const { data, setData, post, processing } = useForm({
        product_id: product.id,
        variant_id: variants[0]?.id ?? '',
        qty: 1,
        size: '',
        color: '',
    });

    const addToCart = () => post('/cart/add', { preserveScroll: true });

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

                <SectionCard noPadding className="mb-4">
                    <div className="grid lg:grid-cols-2 gap-0">
                        <div className="p-4 space-y-3">
                            <img
                                src={activeImage}
                                alt={product.name}
                                className="w-full rounded-lg aspect-square object-cover bg-muted"
                            />
                            {images.length > 1 && (
                                <div className="flex gap-2 overflow-x-auto">
                                    {images.map((url, i) => (
                                        <button
                                            key={i}
                                            type="button"
                                            onClick={() => setActiveImage(url)}
                                            className={cn(
                                                'h-16 w-16 shrink-0 rounded border-2 overflow-hidden',
                                                activeImage === url ? 'border-primary' : 'border-transparent',
                                            )}
                                        >
                                            <img src={url} alt="" className="h-full w-full object-cover" />
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="p-4 lg:sticky lg:top-36 lg:self-start border-t lg:border-t-0 lg:border-l">
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
                            <h1 className="text-xl font-bold">{product.name}</h1>
                            {product.ratingAvg ? (
                                <p className="text-sm text-muted-foreground mt-1">
                                    ★ {product.ratingAvg} ({product.reviewCount} ulasan)
                                </p>
                            ) : null}
                            <div className="flex items-baseline gap-2 mt-3">
                                <span className="text-2xl font-bold text-primary">
                                    {formatRupiah(product.finalPrice)}
                                </span>
                                {product.salePrice && (
                                    <span className="text-sm line-through text-muted-foreground">
                                        {formatRupiah(product.price)}
                                    </span>
                                )}
                            </div>
                            {product.description && (
                                <div
                                    className="prose prose-sm mt-4 max-w-none text-muted-foreground"
                                    dangerouslySetInnerHTML={{ __html: product.description }}
                                />
                            )}
                            <div className="mt-5 space-y-3">
                                {variants.length > 0 && (
                                    <div>
                                        <Label className="text-xs">Varian</Label>
                                        <select
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm mt-1"
                                            value={data.variant_id}
                                            onChange={(e) => setData('variant_id', Number(e.target.value))}
                                        >
                                            {variants.map((v) => (
                                                <option key={v.id} value={v.id}>
                                                    {v.size ?? v.sku}{v.color ? ` - ${v.color}` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                                <div>
                                    <Label htmlFor="qty" className="text-xs">Jumlah</Label>
                                    <Input
                                        id="qty"
                                        type="number"
                                        min={1}
                                        max={99}
                                        value={data.qty}
                                        onChange={(e) => setData('qty', Number(e.target.value))}
                                        className="w-24 h-9 mt-1"
                                    />
                                </div>
                                <p className="text-xs text-muted-foreground">Stok: {productStock}</p>
                                <div className="flex gap-2 pt-1">
                                    <Button onClick={addToCart} disabled={processing} className="flex-1">
                                        + Keranjang
                                    </Button>
                                    <Button variant="outline" onClick={handleToggleWishlist} disabled={wishlistLoading}>
                                        {inWishlist ? '♥' : '♡'}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </SectionCard>

                {reviews.length > 0 && (
                    <SectionCard title="Ulasan Pembeli" className="mb-4">
                        <div className="space-y-3">
                            {reviews.map((r) => (
                                <div key={r.id} className="border rounded-lg p-3">
                                    <div className="flex justify-between text-sm">
                                        <span className="font-medium">{r.customerName}</span>
                                        <span className="text-primary">★ {r.rating}</span>
                                    </div>
                                    <p className="text-sm text-muted-foreground mt-1">{r.comment}</p>
                                </div>
                            ))}
                        </div>
                    </SectionCard>
                )}

                {relatedProducts.length > 0 && (
                    <SectionCard title="Produk Terkait">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                            {relatedProducts.map((p) => (
                                <ProductCard key={p.id} product={p} />
                            ))}
                        </div>
                    </SectionCard>
                )}
            </PageContainer>
        </GuestLayout>
    );
}
