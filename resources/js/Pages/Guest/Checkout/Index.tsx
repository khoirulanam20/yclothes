import { Head, Link, router, useForm } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { CourierSelect, type CourierOption } from '@/components/storefront/CourierSelect';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { WilayahSelect, type WilayahValue } from '@/components/storefront/WilayahSelect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/admin/FieldError';
import { FetchError, fetchJson } from '@/lib/fetchJson';
import { formatRupiah } from '@/lib/utils';

type CartItem = { productName: string; qty: number; subtotal: number };
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
type PaymentMethodOption = {
    id: string;
    label: string;
    type: 'gateway' | 'manual' | 'cod';
    banks?: Bank[];
    comingSoon?: boolean;
};

type Props = {
    items: CartItem[]; pricing: Pricing; banks: Bank[];
    shippingMode: 'manual' | 'biteship';
    hasPhysical: boolean;
    paymentMethods: PaymentMethodOption[];
    paymentMethodsComingSoon?: PaymentMethodOption[];
    customer?: { name: string; email: string; phone?: string | null } | null;
    addresses: Address[];
    newsletterOptInEnabled?: boolean;
    newsletterOptInLabel?: string;
};

const emptyWilayah = (): WilayahValue => ({
    provinceCode: '', provinceName: '', regencyCode: '', regencyName: '',
    districtCode: '', districtName: '', villageCode: '', villageName: '', postalCode: '',
});

function resolveDefaultPaymentMethod(options: PaymentMethodOption[]): string {
    const first = options[0];
    if (!first) return '';
    if (first.id === 'bank_transfer' && first.banks?.[0]) {
        return `bank_${first.banks[0].id}`;
    }
    return first.id;
}

export default function Index({
    items, pricing, shippingMode, hasPhysical, paymentMethods, paymentMethodsComingSoon = [], customer, addresses,
    newsletterOptInEnabled = false, newsletterOptInLabel,
}: Props) {
    const defaultPaymentMethod = resolveDefaultPaymentMethod(paymentMethods);
    const bankTransferOption = paymentMethods.find((m) => m.id === 'bank_transfer');
    const bankList = bankTransferOption?.banks ?? [];

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
        courier_code: '',
        courier_service_code: '',
        shipping_option_key: '',
        payment_method: defaultPaymentMethod,
        address_id: '' as number | '',
        newsletter_opt_in: false,
    });

    const [courierOptions, setCourierOptions] = useState<CourierOption[]>([]);
    const [courierLoading, setCourierLoading] = useState(false);
    const [courierError, setCourierError] = useState<string | null>(null);

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

    const fetchCouriers = useCallback(async (regencyCode: string, postalCode: string) => {
        if (!hasPhysical || !regencyCode) {
            setCourierOptions([]);
            return;
        }

        if (shippingMode === 'biteship' && !postalCode) {
            setCourierOptions([]);
            setCourierError('Isi kode pos untuk melihat ongkir.');
            return;
        }

        setCourierLoading(true);
        setCourierError(null);

        try {
            const res = await fetchJson<{
                options: CourierOption[];
            }>('/checkout/shipping-options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    regency_code: regencyCode,
                    postal_code: postalCode,
                }),
            });

            setCourierOptions(res.options);
            if (res.options.length > 0 && !res.options.some((o) => o.optionKey === data.shipping_option_key)) {
                const first = res.options[0];
                setData({
                    ...data,
                    shipping_option_key: first.optionKey,
                    courier_code: first.courierCode,
                    courier_service_code: first.courierServiceCode ?? '',
                });
            }
        } catch (error) {
            setCourierOptions([]);
            setCourierError(error instanceof FetchError ? 'Gagal memuat opsi ekspedisi.' : 'Terjadi kesalahan.');
        } finally {
            setCourierLoading(false);
        }
    }, [hasPhysical, shippingMode, data, setData]);

    useEffect(() => {
        if (data.regency_code) {
            fetchCouriers(data.regency_code, data.postal_code);
        }
    }, [data.regency_code, data.postal_code, fetchCouriers]);

    const shippingCost = useMemo(() => {
        if (!hasPhysical) return 0;
        return courierOptions.find((o) => o.optionKey === data.shipping_option_key)?.cost ?? 0;
    }, [courierOptions, data.shipping_option_key, hasPhysical]);

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

    const applyCoupon = (e: React.SyntheticEvent) => {
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
            address_id: formData.address_id === '' ? null : formData.address_id,
        }));
        post('/checkout/process');
    };

    const selectedBankId = data.payment_method.startsWith('bank_')
        ? Number(data.payment_method.replace('bank_', ''))
        : bankList[0]?.id ?? 0;

    const isBankTransferSelected = data.payment_method.startsWith('bank_');
    const canSubmit = paymentMethods.length > 0 && (!hasPhysical || (data.shipping_option_key && courierOptions.length > 0));

    return (
        <GuestLayout>
            <Head title="Checkout" />
            <PageContainer narrow compact className="max-w-2xl lg:max-w-3xl">
                <Breadcrumb items={[{ label: 'Beranda', href: '/' }, { label: 'Keranjang', href: '/cart' }, { label: 'Checkout' }]} />

                <form id="checkout-form" onSubmit={submit} className="grid min-w-0 gap-4 pb-24 lg:grid-cols-3 lg:pb-0">
                    <div className="order-2 min-w-0 space-y-4 lg:order-none lg:col-span-2">
                        {addresses.length > 0 && (
                            <SectionCard title="Alamat Pengiriman" className="store-card">
                                <p className="mb-3 text-xs uppercase tracking-wide text-muted-foreground">Pilih alamat tersimpan</p>
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

                        <SectionCard title="Data Pengiriman" className="store-card">
                            <p className="mb-3 text-xs uppercase tracking-wide text-muted-foreground">Informasi penerima</p>
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

                                {hasPhysical && (
                                    <div>
                                        <Label className="text-xs">
                                            {shippingMode === 'biteship' ? 'Pilih Ekspedisi & Layanan' : 'Jasa Ekspedisi'}
                                        </Label>
                                        <div className="mt-2">
                                            <CourierSelect
                                                options={courierOptions}
                                                value={data.shipping_option_key}
                                                onChange={(opt) => setData({
                                                    ...data,
                                                    shipping_option_key: opt.optionKey,
                                                    courier_code: opt.courierCode,
                                                    courier_service_code: opt.courierServiceCode ?? '',
                                                })}
                                                loading={courierLoading}
                                                error={courierError}
                                                disabled={!data.regency_code}
                                                showService={shippingMode === 'biteship'}
                                            />
                                        </div>
                                        <FieldError message={errors.courier_code || errors.courier_service_code} />
                                    </div>
                                )}
                            </div>
                        </SectionCard>

                        <SectionCard title="Metode Pembayaran" className="store-card">
                            <p className="mb-3 text-xs uppercase tracking-wide text-muted-foreground">Pilih metode</p>
                            <div className="space-y-2">
                                {paymentMethods.length === 0 && (
                                    <p className="text-sm text-muted-foreground">Tidak ada metode pembayaran tersedia.</p>
                                )}
                                {paymentMethodsComingSoon.map((method) => (
                                    <div
                                        key={method.id}
                                        className="flex items-center justify-between gap-2 text-sm p-2 rounded border border-dashed bg-muted/30 opacity-70 cursor-not-allowed"
                                        aria-disabled
                                    >
                                        <span className="text-muted-foreground">{method.label}</span>
                                        <span className="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                            Segera Hadir
                                        </span>
                                    </div>
                                ))}
                                {paymentMethods.map((method) => {
                                    if (method.id === 'bank_transfer') {
                                        return (
                                            <div key={method.id}>
                                                <label className="flex min-w-0 cursor-pointer items-center gap-3 break-words rounded-xl border p-3 transition-colors hover:bg-muted/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                                    <input
                                                        type="radio"
                                                        name="pm"
                                                        checked={isBankTransferSelected}
                                                        onChange={() => setData('payment_method', `bank_${selectedBankId || bankList[0]?.id}`)}
                                                    />
                                                    {method.label}
                                                </label>
                                                {isBankTransferSelected && bankList.length > 0 && (
                                                    <select
                                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm mt-2"
                                                        value={selectedBankId}
                                                        onChange={(e) => setData('payment_method', `bank_${Number(e.target.value)}`)}
                                                    >
                                                        {bankList.map((b) => (
                                                            <option key={b.id} value={b.id}>{b.bankName} — {b.accountNumber}</option>
                                                        ))}
                                                    </select>
                                                )}
                                            </div>
                                        );
                                    }

                                    return (
                                        <label
                                            key={method.id}
                                            className="flex min-w-0 cursor-pointer items-center gap-3 break-words rounded-xl border p-3 text-sm transition-colors hover:bg-muted/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                                        >
                                            <input
                                                type="radio"
                                                name="pm"
                                                checked={data.payment_method === method.id}
                                                onChange={() => setData('payment_method', method.id)}
                                            />
                                            {method.label}
                                        </label>
                                    );
                                })}
                                <FieldError message={errors.payment_method} />
                            </div>
                        </SectionCard>
                    </div>

                    <div className="order-1 min-w-0 lg:order-none lg:sticky lg:top-24 lg:self-start">
                        <SectionCard title="Ringkasan Transaksi" className="store-card overflow-visible">
                            <p className="mb-3 text-xs text-muted-foreground">Cek ringkasan transaksimu, yuk</p>
                            <div className="min-w-0 space-y-2 text-sm">
                                {items.map((item, i) => (
                                    <div key={i} className="flex min-w-0 items-start justify-between gap-3">
                                        <span className="min-w-0 flex-1 text-muted-foreground line-clamp-2">{item.productName} ×{item.qty}</span>
                                        <span className="shrink-0 tabular-nums">{formatRupiah(item.subtotal)}</span>
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
                                    <div className="flex gap-2">
                                        <Input
                                            placeholder="Kode kupon"
                                            value={couponForm.data.coupon_code}
                                            onChange={(e) => couponForm.setData('coupon_code', e.target.value)}
                                            className="h-9"
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    applyCoupon(e);
                                                }
                                            }}
                                        />
                                        <Button type="button" variant="outline" size="sm" disabled={couponForm.processing} onClick={applyCoupon}>
                                            Apply
                                        </Button>
                                    </div>
                                )}
                                <div className="border-t pt-2 space-y-1">
                                    <div className="flex min-w-0 items-center justify-between gap-3">
                                        <span>Subtotal</span>
                                        <span className="shrink-0 tabular-nums">{formatRupiah(pricing.subtotal)}</span>
                                    </div>
                                    {pricing.discountAmount > 0 && (
                                        <div className="flex min-w-0 items-center justify-between gap-3 text-green-600">
                                            <span className="min-w-0 truncate">Diskon{pricing.couponCode ? ` (${pricing.couponCode})` : ''}</span>
                                            <span className="shrink-0 tabular-nums">-{formatRupiah(pricing.discountAmount)}</span>
                                        </div>
                                    )}
                                    {pricing.taxAmount > 0 && (
                                        <div className="flex min-w-0 items-center justify-between gap-3">
                                            <span>Pajak</span>
                                            <span className="shrink-0 tabular-nums">{formatRupiah(pricing.taxAmount)}</span>
                                        </div>
                                    )}
                                    {hasPhysical && (
                                        <div className="flex min-w-0 items-center justify-between gap-3">
                                            <span>Ongkir</span>
                                            <span className="shrink-0 tabular-nums">{formatRupiah(shippingCost)}</span>
                                        </div>
                                    )}
                                    <div className="flex min-w-0 items-center justify-between gap-3 pt-1 text-base font-bold">
                                        <span>Total</span>
                                        <span className="shrink-0 tabular-nums text-primary">{formatRupiah(grandTotal)}</span>
                                    </div>
                                </div>
                            </div>
                            {newsletterOptInEnabled && (
                                <label className="flex items-start gap-2 text-sm mt-4 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        className="mt-0.5 size-4 rounded border"
                                        checked={Boolean(data.newsletter_opt_in)}
                                        onChange={(e) => setData('newsletter_opt_in', e.target.checked)}
                                    />
                                    <span>{newsletterOptInLabel ?? 'Berlangganan newsletter untuk promo & update'}</span>
                                </label>
                            )}
                            <Button type="submit" form="checkout-form" className="mt-4 hidden w-full lg:inline-flex" size="lg" disabled={processing || !canSubmit}>
                                Bayar Sekarang
                            </Button>
                            <Button variant="outline" className="mt-2 hidden w-full lg:inline-flex" asChild>
                                <Link href="/cart">Kembali ke Keranjang</Link>
                            </Button>
                        </SectionCard>
                    </div>
                </form>

                <div className="fixed inset-x-0 bottom-0 z-30 border-t bg-card px-4 pb-[calc(1rem+env(safe-area-inset-bottom,0px))] pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] lg:hidden">
                    <div className="mx-auto flex max-w-2xl items-center gap-2">
                        <div className="min-w-0 flex-1">
                            <p className="text-xs text-muted-foreground">Total</p>
                            <p className="truncate text-base font-bold tabular-nums text-primary sm:text-lg">{formatRupiah(grandTotal)}</p>
                        </div>
                        <Button
                            type="submit"
                            form="checkout-form"
                            size="lg"
                            className="h-11 shrink-0 whitespace-nowrap px-4 text-sm sm:px-6 sm:text-base"
                            disabled={processing || !canSubmit}
                        >
                            Bayar Sekarang
                        </Button>
                    </div>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
