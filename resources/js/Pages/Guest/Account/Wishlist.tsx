import { Head, Link, router } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { ProductCard, type ProductCardData } from '@/components/ProductCard';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';

type Props = { products: ProductCardData[] };

export default function Wishlist({ products }: Props) {
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
                                onClick={() =>
                                    router.post('/account/wishlist/toggle', { product_id: p.id }, { preserveScroll: true })
                                }
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
