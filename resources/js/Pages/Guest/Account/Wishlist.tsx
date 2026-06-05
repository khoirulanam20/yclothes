import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { toggleWishlist } from '@/lib/toggleWishlist';
import { guestToast } from '@/lib/guestToast';

type Props = { products: ProductCardData[] };

export default function Wishlist({ products: initialProducts }: Props) {
    const [products, setProducts] = useState(initialProducts);
    const [removingId, setRemovingId] = useState<number | null>(null);

    const removeFromWishlist = async (productId: number) => {
        if (removingId !== null) {
            return;
        }

        setRemovingId(productId);

        try {
            const result = await toggleWishlist(productId);

            if (!result.in_wishlist) {
                setProducts((current) => current.filter((p) => p.id !== productId));
                guestToast.success('Dihapus dari wishlist.');
            }
        } catch (error) {
            guestToast.error(error instanceof Error ? error.message : 'Gagal memperbarui wishlist.');
        } finally {
            setRemovingId(null);
        }
    };

    return (
        <AccountLayout title="Wishlist">
            <Head title="Wishlist" />
            {products.length === 0 ? (
                <SectionCard className="text-center py-8">
                    <p className="text-muted-foreground mb-4">Wishlist kosong.</p>
                    <Button asChild><Link href="/products">Jelajahi Produk</Link></Button>
                </SectionCard>
            ) : (
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                    {products.map((p) => (
                        <div key={p.id} className="relative">
                            <ProductCard product={p} />
                            <Button
                                variant="ghost"
                                size="sm"
                                className="absolute top-2 right-2 h-7 w-7 p-0 bg-card/80"
                                disabled={removingId === p.id}
                                onClick={() => removeFromWishlist(p.id)}
                            >
                                ✕
                            </Button>
                        </div>
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}
