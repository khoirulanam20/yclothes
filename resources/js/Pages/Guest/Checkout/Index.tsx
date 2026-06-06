import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { WilayahSelect, type WilayahValue } from '@/components/storefront/WilayahSelect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/admin/FieldError';
import { formatRupiah } from '@/lib/utils';

type CartItem = { productName: string; qty: number; subtotal: number };
type City = { id: number; cityName: string; regencyCode?: string | null; calculatedCost?: number };
type Bank = { id: number; bankName: string; accountNumber: string; accountName: string };
type Address = {
    id: number; label: string; recipientName: string; phone: string; streetAddress: string;
    provinceCode?: string; provinceName?: string; regencyCode?: string; regencyName?: string;
    districtCode?: string; districtName?: string; villageCode?: string; villageName?: string;
    postalCode?: string; city?: string;
};
type Pricing = {
    subtotal: number; taxAmount: number; discountAmount: number;
    totalWeight: number; totalQty: number; couponCode?: string | null;
};

type Props = {
    items: CartItem[]; pricing: Pricing; cities: City[]; banks: Bank[];
    midtransActive: boolean; customer?: { name: string; email: string; phone?: string | null } | null;
    addresses: Address[];
};

const emptyWilayah = (): WilayahValue => ({
    provinceCode: '', provinceName: '', regencyCode: '', regencyName: '',
    districtCode: '', districtName: '', villageCode: '', villageName: '', postalCode: '',
});

export default function Index({ items, pricing, cities, banks, midtransActive, customer, addresses }: Props) {
    const defaultBankId = banks[0]?.id;
    const defaultPaymentMethod = midtransActive ? 'midtrans' : defaultBankId ? `bank_${defaultBankId}` : '';

    const couponForm = useForm({
        coupon_code: pricing.couponCode ?? '',
        redirect: 'checkout' as const,
    });

    const { data, setData, post, processing, errors, transform } = useForm({
        customer_name: customer?.name ?? '',
        customer_email: customer?.email ?? '',
        customer_phone: customer?.phone ?? '',
        shipping_address: '',
        province_code: '',
        province_name: '',
        regency_code: '',
        regency_name: '',
        district_code: '',
        district_name: '',
        village_code: '',
        village_name: '',
        postal_code: '',
        shipping_city: cities[0]?.id ?? '',
        payment_method: defaultPaymentMethod,
        address_id: '' as number | '',
    });

    const wilayahValue: WilayahValue = {
        provinceCode: data.province_code,
        provinceName: data.province_name,
        regencyCode: data.regency_code,
        regencyName: data.regency_name,
        districtCode: data.district_code,
        districtName: data.district_name,
        villageCode: data.village_code,
        villageName: data.village_name,
        postalCode: data.postal_code,
    };

    const matchedCityId = useMemo(() => {
        if (!data.regency_code) return data.shipping_city;
        const match = cities.find((c) => c.regencyCode === data.regency_code);
        return match?.id ?? data.shipping_city;
    }, [cities, data.regency_code, data.shipping_city]);

    const shippingCost = cities.find((c) => c.id === Number(matchedCityId))?.calculatedCost ?? 0;
    const grandTotal = pricing.subtotal - pricing.discountAmount + pricing.taxAmount + shippingCost;

    const applyAddress = (addr: Address) => {
        setData({
            ...data,
            address_id: addr.id,
            customer_name: addr.recipientName,
            customer_phone: addr.phone,
            shipping_address: addr.streetAddress,
            province_code: addr.provinceCode ?? '',
            province_name: addr.provinceName ?? '',
            regency_code: addr.regencyCode ?? '',
            regency_name: addr.regencyName ?? addr.city ?? '',
            district_code: addr.districtCode ?? '',
            district_name: addr.districtName ?? '',
            village_code: addr.villageCode ?? '',
            village_name: addr.villageName ?? '',
            postal_code: addr.postalCode ?? '',
        });
    };

    const applyCoupon = (e: React.FormEvent) => {
        e.preventDefault();
        couponForm.post('/cart/coupon', { preserveScroll: true });
    };

    const removeCoupon = () => {
        router.delete('/cart/coupon', {
            data: { redirect: 'checkout' },
            preserveScroll: true,
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        transform((formData) => ({
            ...formData,
            shipping_city: matchedCityId,
            address_id: formData.address_id === '' ? null : formData.address_id,
        }));
        post('/checkout/process');
    };

    const selectedBankId = data.payment_method.startsWith('bank_')
        ? Number(data.payment_method.replace('bank_', ''))
        : defaultBankId ?? 0;

    return (
        <GuestLayout>
            <Head title="Checkout" />
            <PageContainer>
                <Breadcrumb items={[{ label: 'Beranda', href: '/' }, { label: 'Keranjang', href: '/cart' }, { label: 'Checkout' }]} />

                <form onSubmit={submit} className="grid lg:grid-cols-3 gap-4">
                    <div className="lg:col-span-2 space-y-4">
                        {addresses.length > 0 && (
                            <SectionCard title="Alamat Tersimpan">
                                <div className="flex flex-wrap gap-2">
                                    {addresses.map((addr) => (
                                        <Button
                                            key={addr.id}
                                            type="button"
                                            size="sm"
                                            variant={data.address_id === addr.id ? 'default' : 'outline'}
                                            onClick={() => applyAddress(addr)}
                                        >
                                            {addr.label}
                                        </Button>
                                    ))}
                                </div>
                            </SectionCard>
                        )}

                        <SectionCard title="Data Pengiriman">
                            <div className="space-y-3">
                                <div className="grid md:grid-cols-2 gap-3">
                                    <div>
                                        <Label htmlFor="customer_name" className="text-xs">Nama</Label>
                                        <Input id="customer_name" value={data.customer_name} onChange={(e) => setData('customer_name', e.target.value)} required className="h-9" />
                                        <FieldError message={errors.customer_name} />
                                    </div>
                                    <div>
                                        <Label htmlFor="customer_phone" className="text-xs">WhatsApp</Label>
                                        <Input id="customer_phone" value={data.customer_phone} onChange={(e) => setData('customer_phone', e.target.value)} required className="h-9" />
                                        <FieldError message={errors.customer_phone} />
                                    </div>
                                </div>
                                <div>
                                    <Label htmlFor="customer_email" className="text-xs">Email</Label>
                                    <Input id="customer_email" type="email" value={data.customer_email} onChange={(e) => setData('customer_email', e.target.value)} required className="h-9" />
                                    <FieldError message={errors.customer_email} />
                                </div>
                                <div>
                                    <Label htmlFor="shipping_address" className="text-xs">Alamat Jalan / RT/RW</Label>
                                    <Textarea id="shipping_address" rows={2} value={data.shipping_address} onChange={(e) => setData('shipping_address', e.target.value)} required />
                                    <FieldError message={errors.shipping_address} />
                                </div>
                                <WilayahSelect
                                    value={wilayahValue}
                                    onChange={(w) => setData({
                                        ...data,
                                        address_id: '',
                                        province_code: w.provinceCode,
                                        province_name: w.provinceName,
                                        regency_code: w.regencyCode,
                                        regency_name: w.regencyName,
                                        district_code: w.districtCode,
                                        district_name: w.districtName,
                                        village_code: w.villageCode,
                                        village_name: w.villageName,
                                        postal_code: w.postalCode,
                                    })}
                                />
                                <FieldError message={errors.province_code || errors.regency_code || errors.district_code} />
                                <div>
                                    <Label className="text-xs">Ongkos Kirim (per kab/kota)</Label>
                                    <select
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        value={matchedCityId}
                                        onChange={(e) => setData('shipping_city', Number(e.target.value))}
                                    >
                                        {cities.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.cityName} — {formatRupiah(c.calculatedCost ?? 0)}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-muted-foreground mt-1">Integrasi kurir API — segera hadir</p>
                                    <FieldError message={errors.shipping_city} />
                                </div>
                            </div>
                        </SectionCard>

                        <SectionCard title="Metode Pembayaran">
                            <div className="space-y-2">
                                {midtransActive && (
                                    <label className="flex items-center gap-2 text-sm p-2 rounded border cursor-pointer hover:bg-muted/50">
                                        <input type="radio" name="pm" checked={data.payment_method === 'midtrans'} onChange={() => setData('payment_method', 'midtrans')} />
                                        Midtrans (Online)
                                    </label>
                                )}
                                <label className="flex items-center gap-2 text-sm p-2 rounded border cursor-pointer hover:bg-muted/50">
                                    <input
                                        type="radio"
                                        name="pm"
                                        checked={data.payment_method.startsWith('bank_')}
                                        onChange={() => setData('payment_method', `bank_${selectedBankId || defaultBankId}`)}
                                    />
                                    Transfer Bank
                                </label>
                                {data.payment_method.startsWith('bank_') && (
                                    <select
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        value={selectedBankId}
                                        onChange={(e) => setData('payment_method', `bank_${Number(e.target.value)}`)}
                                    >
                                        {banks.map((b) => (
                                            <option key={b.id} value={b.id}>{b.bankName} — {b.accountNumber}</option>
                                        ))}
                                    </select>
                                )}
                                <FieldError message={errors.payment_method} />
                            </div>
                        </SectionCard>
                    </div>

                    <div className="lg:sticky lg:top-36 lg:self-start">
                        <SectionCard title="Ringkasan Pesanan">
                            <div className="space-y-2 text-sm">
                                {items.map((item, i) => (
                                    <div key={i} className="flex justify-between gap-2">
                                        <span className="text-muted-foreground truncate">{item.productName} ×{item.qty}</span>
                                        <span className="shrink-0">{formatRupiah(item.subtotal)}</span>
                                    </div>
                                ))}
                                {pricing.couponCode ? (
                                    <div className="flex items-center justify-between gap-2 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                                        <span>Kupon <strong>{pricing.couponCode}</strong> aktif</span>
                                        <Button type="button" variant="ghost" size="sm" className="h-7 text-green-800 hover:text-green-900" onClick={removeCoupon}>
                                            Hapus
                                        </Button>
                                    </div>
                                ) : (
                                    <form onSubmit={applyCoupon} className="flex gap-2">
                                        <Input
                                            placeholder="Kode kupon"
                                            value={couponForm.data.coupon_code}
                                            onChange={(e) => couponForm.setData('coupon_code', e.target.value)}
                                            className="h-9"
                                        />
                                        <Button type="submit" variant="outline" size="sm" disabled={couponForm.processing}>
                                            Apply
                                        </Button>
                                    </form>
                                )}
                                <div className="border-t pt-2 space-y-1">
                                    <div className="flex justify-between"><span>Subtotal</span><span>{formatRupiah(pricing.subtotal)}</span></div>
                                    {pricing.discountAmount > 0 && (
                                        <div className="flex justify-between text-green-600">
                                            <span>Diskon{pricing.couponCode ? ` (${pricing.couponCode})` : ''}</span>
                                            <span>-{formatRupiah(pricing.discountAmount)}</span>
                                        </div>
                                    )}
                                    {pricing.taxAmount > 0 && (
                                        <div className="flex justify-between"><span>Pajak</span><span>{formatRupiah(pricing.taxAmount)}</span></div>
                                    )}
                                    <div className="flex justify-between"><span>Ongkir</span><span>{formatRupiah(shippingCost)}</span></div>
                                    <div className="flex justify-between font-bold text-base pt-1">
                                        <span>Total</span>
                                        <span className="text-primary">{formatRupiah(grandTotal)}</span>
                                    </div>
                                </div>
                            </div>
                            <Button type="submit" className="w-full mt-4" disabled={processing}>Buat Pesanan</Button>
                            <Button variant="outline" className="w-full mt-2" asChild>
                                <Link href="/cart">Kembali ke Keranjang</Link>
                            </Button>
                        </SectionCard>
                    </div>
                </form>
            </PageContainer>
        </GuestLayout>
    );
}
