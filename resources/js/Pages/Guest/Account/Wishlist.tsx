import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import { type ProductCardData } from '@/components/ProductCard';
import { AccountPageHeader } from '@/components/storefront/AccountPageHeader';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { ProductGrid } from '@/components/storefront/ProductGrid';
import { Button } from '@/components/ui/button';

type Props = { products: ProductCardData[] };

export default function Wishlist({ products: initialProducts }: Props) {
    const [products, setProducts] = useState(initialProducts);

    const handleWishlistToggle = (productId: number, inWishlist: boolean) => {
        if (!inWishlist) {
            setProducts((current) => current.filter((p) => p.id !== productId));
        }
    };

    return (
        <AccountLayout>
            <Head title="Wishlist" />
            <AccountPageHeader title="Wishlist" />
            {products.length === 0 ? (
                <AccountPageShell title="Wishlist kosong">
                    <div className="py-6 text-center">
                        <p className="mb-4 text-muted-foreground">Simpan produk favorit Anda di sini.</p>
                        <Button asChild><Link href="/products">Jelajahi Produk</Link></Button>
                    </div>
                </AccountPageShell>
            ) : (
                <ProductGrid
                    products={products}
                    showWishlist
                    wishlistMode
                    onWishlistToggle={handleWishlistToggle}
                />
            )}
        </AccountLayout>
    );
}
