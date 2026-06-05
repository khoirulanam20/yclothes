import { Head, Link, router, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatRupiah } from '@/lib/utils';

type CartItem = {
    key: string; qty: number; size?: string; color?: string; unitPrice: number; subtotal: number;
    product: { id: number; name: string; slug: string; imageUrl: string };
};
type Pricing = {
    subtotal: number; taxAmount: number; discountAmount: number; freeShipping: boolean;
    couponCode?: string | null; totalQty: number;
};

type Props = { items: CartItem[]; pricing: Pricing };

export default function Index({ items, pricing }: Props) {
    const couponForm = useForm({ coupon_code: '' });

    const updateQty = (key: string, qty: number) => router.post('/cart/update', { key, qty }, { preserveScroll: true });
    const removeItem = (key: string) => router.post('/cart/remove', { key }, { preserveScroll: true });
    const applyCoupon = (e: React.FormEvent) => {
        e.preventDefault();
        couponForm.post('/cart/coupon', { preserveScroll: true });
    };

    const total = pricing.subtotal - pricing.discountAmount + pricing.taxAmount;

    return (
        <GuestLayout>
            <Head title="Keranjang" />
            <PageContainer>
                <Breadcrumb items={[{ label: 'Beranda', href: '/' }, { label: 'Keranjang' }]} />

                {items.length === 0 ? (
                    <SectionCard className="text-center py-12">
                        <p className="text-muted-foreground mb-4">Keranjang kosong.</p>
                        <Button asChild><Link href="/products">Belanja Sekarang</Link></Button>
                    </SectionCard>
                ) : (
                    <div className="grid lg:grid-cols-3 gap-4">
                        <div className="lg:col-span-2 space-y-3">
                            {items.map((item) => (
                                <SectionCard key={item.key} noPadding>
                                    <div className="p-4 flex gap-3">
                                        <img
                                            src={item.product.imageUrl}
                                            alt=""
                                            className="h-20 w-20 object-cover rounded-lg shrink-0"
                                        />
                                        <div className="flex-1 min-w-0">
                                            <Link
                                                href={`/products/${item.product.slug}`}
                                                className="font-medium text-sm hover:text-primary line-clamp-2"
                                            >
                                                {item.product.name}
                                            </Link>
                                            <p className="text-sm text-primary font-semibold mt-1">
                                                {formatRupiah(item.unitPrice)}
                                            </p>
                                            <div className="flex items-center gap-2 mt-2">
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    max={99}
                                                    value={item.qty}
                                                    onChange={(e) => updateQty(item.key, Number(e.target.value))}
                                                    className="w-16 h-8 text-sm"
                                                />
                                                <Button variant="ghost" size="sm" onClick={() => removeItem(item.key)}>
                                                    Hapus
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="font-semibold text-sm shrink-0">
                                            {formatRupiah(item.subtotal)}
                                        </div>
                                    </div>
                                </SectionCard>
                            ))}
                        </div>

                        <div className="lg:sticky lg:top-36 lg:self-start">
                            <SectionCard title="Ringkasan Belanja">
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Subtotal ({pricing.totalQty} item)</span>
                                        <span>{formatRupiah(pricing.subtotal)}</span>
                                    </div>
                                    {pricing.discountAmount > 0 && (
                                        <div className="flex justify-between text-primary">
                                            <span>Diskon</span>
                                            <span>-{formatRupiah(pricing.discountAmount)}</span>
                                        </div>
                                    )}
                                    {pricing.taxAmount > 0 && (
                                        <div className="flex justify-between">
                                            <span>Pajak</span>
                                            <span>{formatRupiah(pricing.taxAmount)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between font-bold text-base pt-2 border-t">
                                        <span>Total</span>
                                        <span className="text-primary">{formatRupiah(total)}</span>
                                    </div>
                                </div>
                                <form onSubmit={applyCoupon} className="flex gap-2 pt-4">
                                    <Input
                                        placeholder="Kode kupon"
                                        value={couponForm.data.coupon_code}
                                        onChange={(e) => couponForm.setData('coupon_code', e.target.value)}
                                        className="h-9"
                                    />
                                    <Button type="submit" variant="outline" size="sm">Apply</Button>
                                </form>
                                <Button className="w-full mt-4" asChild>
                                    <Link href="/checkout">Checkout</Link>
                                </Button>
                            </SectionCard>
                        </div>
                    </div>
                )}
            </PageContainer>
        </GuestLayout>
    );
}
