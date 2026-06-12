import { Head, Link, router, useForm } from '@inertiajs/react';
import { Minus, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { type ProductCardData } from '@/components/ProductCard';
import GuestLayout from '@/Layouts/GuestLayout';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { AppliedCouponBanner } from '@/components/storefront/AppliedCouponBanner';
import { PageContainer } from '@/components/storefront/PageContainer';
import { ProductGrid } from '@/components/storefront/ProductGrid';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn, formatRupiah } from '@/lib/utils';

type CartItem = {
    key: string; qty: number; size?: string; color?: string; unitPrice: number; subtotal: number;
    product: { id: number; name: string; slug: string; imageUrl: string };
};
type Pricing = {
    subtotal: number; taxAmount: number; discountAmount: number; freeShipping: boolean;
    couponCode?: string | null; couponApplied?: boolean; totalQty: number;
};

type Props = {
    items: CartItem[];
    pricing: Pricing;
    crossSellProducts?: ProductCardData[];
    selectedKeys?: string[];
};

export default function Index({ items, pricing, crossSellProducts = [], selectedKeys: initialSelectedKeys = [] }: Props) {
    const allKeys = items.map((item) => item.key);
    const [selectedKeys, setSelectedKeys] = useState<string[]>(
        initialSelectedKeys.length > 0 ? initialSelectedKeys.filter((key) => allKeys.includes(key)) : allKeys,
    );

    const couponForm = useForm({
        coupon_code: pricing.couponCode ?? '',
        redirect: 'cart' as const,
    });

    const selectedItems = useMemo(
        () => items.filter((item) => selectedKeys.includes(item.key)),
        [items, selectedKeys],
    );

    const selectedSubtotal = selectedItems.reduce((sum, item) => sum + item.subtotal, 0);
    const selectedQty = selectedItems.reduce((sum, item) => sum + item.qty, 0);
    const discountRatio = pricing.subtotal > 0 ? pricing.discountAmount / pricing.subtotal : 0;
    const taxRatio = pricing.subtotal > 0 ? pricing.taxAmount / pricing.subtotal : 0;
    const selectedDiscount = Math.round(selectedSubtotal * discountRatio);
    const selectedTax = Math.round(selectedSubtotal * taxRatio);
    const selectedTotal = selectedSubtotal - selectedDiscount + selectedTax;
    const allSelected = items.length > 0 && selectedKeys.length === items.length;

    const toggleItem = (key: string, checked: boolean) => {
        setSelectedKeys((current) => (
            checked ? [...current, key] : current.filter((k) => k !== key)
        ));
    };

    const toggleAll = (checked: boolean) => {
        setSelectedKeys(checked ? allKeys : []);
    };

    const updateQty = (key: string, qty: number) => {
        if (qty < 1) {
            return;
        }
        router.post('/cart/update', { key, qty }, { preserveScroll: true });
    };

    const removeItem = (key: string) => {
        setSelectedKeys((current) => current.filter((k) => k !== key));
        router.post('/cart/remove', { key }, { preserveScroll: true });
    };

    const applyCoupon = (e: React.FormEvent) => {
        e.preventDefault();
        couponForm.post('/cart/coupon', { preserveScroll: true });
    };

    const removeCoupon = () => {
        router.delete('/cart/coupon', {
            data: { redirect: 'cart' },
            preserveScroll: true,
        });
    };

    const checkoutSelected = () => {
        if (selectedKeys.length === 0) {
            return;
        }
        router.post('/cart/checkout-selection', { keys: selectedKeys });
    };

    return (
        <GuestLayout>
            <Head title="Keranjang" />
            <PageContainer>
                <Breadcrumb items={[{ label: 'Beranda', href: '/' }, { label: 'Keranjang' }]} />

                {items.length === 0 ? (
                    <SectionCard className="py-12 text-center">
                        <p className="mb-4 text-muted-foreground">Keranjang kosong.</p>
                        <Button asChild><Link href="/products">Belanja Sekarang</Link></Button>
                    </SectionCard>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-3">
                        <div className="space-y-3 lg:col-span-2">
                            <div className="flex items-center gap-3 rounded-lg border bg-card px-4 py-3">
                                <label className="flex cursor-pointer items-center gap-2 text-sm font-medium">
                                    <input
                                        type="checkbox"
                                        checked={allSelected}
                                        onChange={(e) => toggleAll(e.target.checked)}
                                        className="size-4 rounded border-input accent-primary"
                                    />
                                    Pilih Semua ({items.length})
                                </label>
                            </div>

                            {items.map((item) => {
                                const isSelected = selectedKeys.includes(item.key);

                                return (
                                    <SectionCard key={item.key} noPadding className="store-card overflow-hidden">
                                        <div className="flex gap-3 p-4">
                                            <label className="flex shrink-0 cursor-pointer items-start pt-1">
                                                <input
                                                    type="checkbox"
                                                    checked={isSelected}
                                                    onChange={(e) => toggleItem(item.key, e.target.checked)}
                                                    className="size-4 rounded border-input accent-primary"
                                                />
                                            </label>
                                            <Link href={`/products/${item.product.slug}`} className="shrink-0">
                                                <img
                                                    src={item.product.imageUrl}
                                                    alt=""
                                                    className="size-20 rounded-lg border object-cover transition-opacity hover:opacity-90"
                                                />
                                            </Link>
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={`/products/${item.product.slug}`}
                                                    className="line-clamp-2 text-sm font-medium hover:text-primary"
                                                >
                                                    {item.product.name}
                                                </Link>
                                                {(item.size || item.color) && (
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        {[item.size, item.color].filter(Boolean).join(' · ')}
                                                    </p>
                                                )}
                                                <p className="mt-1 text-sm font-bold text-foreground">
                                                    {formatRupiah(item.unitPrice)}
                                                </p>
                                                <div className="mt-3 flex items-center gap-2">
                                                    <div className="flex items-center rounded-lg border">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 rounded-none"
                                                            onClick={() => updateQty(item.key, item.qty - 1)}
                                                            disabled={item.qty <= 1}
                                                        >
                                                            <Minus className="size-3.5" />
                                                        </Button>
                                                        <span className="w-8 text-center text-sm">{item.qty}</span>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 rounded-none"
                                                            onClick={() => updateQty(item.key, item.qty + 1)}
                                                        >
                                                            <Plus className="size-3.5" />
                                                        </Button>
                                                    </div>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-muted-foreground hover:text-destructive"
                                                        onClick={() => removeItem(item.key)}
                                                    >
                                                        <Trash2 className="mr-1 size-3.5" />
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </div>
                                            <div className={cn('shrink-0 text-sm font-semibold', !isSelected && 'text-muted-foreground')}>
                                                {formatRupiah(item.subtotal)}
                                            </div>
                                        </div>
                                    </SectionCard>
                                );
                            })}

                            {crossSellProducts.length > 0 && (
                                <SectionCard title="Lengkapi Belanjaan Anda" className="mt-2">
                                    <ProductGrid products={crossSellProducts} layout="scroll" compact />
                                </SectionCard>
                            )}
                        </div>

                        <div className="lg:sticky lg:top-24 lg:self-start">
                            <SectionCard title="Ringkasan Belanja" className="store-card">
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Subtotal ({selectedQty} item dipilih)
                                        </span>
                                        <span>{formatRupiah(selectedSubtotal)}</span>
                                    </div>
                                    {selectedDiscount > 0 && (
                                        <div className="flex justify-between text-green-600">
                                            <span>Diskon</span>
                                            <span>-{formatRupiah(selectedDiscount)}</span>
                                        </div>
                                    )}
                                    {selectedTax > 0 && (
                                        <div className="flex justify-between">
                                            <span>Pajak</span>
                                            <span>{formatRupiah(selectedTax)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between border-t pt-2 text-base font-bold">
                                        <span>Total</span>
                                        <span className="text-primary">{formatRupiah(selectedTotal)}</span>
                                    </div>
                                </div>
                                {pricing.couponApplied && pricing.couponCode ? (
                                    <AppliedCouponBanner
                                        className="mt-4"
                                        couponCode={pricing.couponCode}
                                        onRemove={removeCoupon}
                                    />
                                ) : (
                                    <form onSubmit={applyCoupon} className="mt-4 flex gap-2">
                                        <Input
                                            placeholder="Kode kupon"
                                            value={couponForm.data.coupon_code}
                                            onChange={(e) => couponForm.setData('coupon_code', e.target.value)}
                                            className="h-9"
                                        />
                                        <Button type="submit" variant="outline" size="sm" disabled={couponForm.processing}>
                                            Pakai
                                        </Button>
                                    </form>
                                )}
                                <Button
                                    className="mt-4 w-full"
                                    size="lg"
                                    disabled={selectedKeys.length === 0}
                                    onClick={checkoutSelected}
                                >
                                    Lanjut Checkout ({selectedKeys.length})
                                </Button>
                            </SectionCard>
                        </div>
                    </div>
                )}
            </PageContainer>
        </GuestLayout>
    );
}
