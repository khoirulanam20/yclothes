import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/admin/FieldError';
import { formatRupiah } from '@/lib/utils';

type CartItem = { productName: string; qty: number; subtotal: number };
type City = { id: number; cityName: string; calculatedCost?: number };
type Bank = { id: number; bankName: string; accountNumber: string; accountName: string };
type Address = { id: number; label: string; recipientName: string; streetAddress: string; city: string };
type Pricing = { subtotal: number; taxAmount: number; discountAmount: number; totalWeight: number; totalQty: number };

type Props = {
    items: CartItem[]; pricing: Pricing; cities: City[]; banks: Bank[];
    midtransActive: boolean; customer?: { name: string; email: string; phone?: string | null } | null;
    addresses: Address[];
};

export default function Index({ items, pricing, cities, banks, midtransActive, customer }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        customer_name: customer?.name ?? '',
        customer_email: customer?.email ?? '',
        customer_phone: customer?.phone ?? '',
        shipping_address: '',
        shipping_city: cities[0]?.cityName ?? '',
        city_id: cities[0]?.id ?? '',
        payment_method: midtransActive ? 'midtrans' : 'bank_transfer',
        bank_id: banks[0]?.id ?? '',
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/checkout/process');
    };

    const shippingCost = cities.find((c) => c.id === Number(data.city_id))?.calculatedCost ?? 0;
    const grandTotal = pricing.subtotal - pricing.discountAmount + pricing.taxAmount + shippingCost;

    return (
        <GuestLayout>
            <Head title="Checkout" />
            <PageContainer>
                <Breadcrumb items={[{ label: 'Beranda', href: '/' }, { label: 'Keranjang', href: '/cart' }, { label: 'Checkout' }]} />

                <form onSubmit={submit} className="grid lg:grid-cols-3 gap-4">
                    <div className="lg:col-span-2 space-y-4">
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
                                    </div>
                                </div>
                                <div>
                                    <Label htmlFor="customer_email" className="text-xs">Email</Label>
                                    <Input id="customer_email" type="email" value={data.customer_email} onChange={(e) => setData('customer_email', e.target.value)} required className="h-9" />
                                </div>
                                <div>
                                    <Label htmlFor="shipping_address" className="text-xs">Alamat Lengkap</Label>
                                    <Textarea id="shipping_address" rows={3} value={data.shipping_address} onChange={(e) => setData('shipping_address', e.target.value)} required />
                                </div>
                                <div>
                                    <Label htmlFor="city_id" className="text-xs">Kota</Label>
                                    <select
                                        id="city_id"
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        value={data.city_id}
                                        onChange={(e) => {
                                            const city = cities.find((c) => c.id === Number(e.target.value));
                                            setData('city_id', Number(e.target.value));
                                            setData('shipping_city', city?.cityName ?? '');
                                        }}
                                    >
                                        {cities.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.cityName} — {formatRupiah(c.calculatedCost ?? 0)}
                                            </option>
                                        ))}
                                    </select>
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
                                    <input type="radio" name="pm" checked={data.payment_method === 'bank_transfer'} onChange={() => setData('payment_method', 'bank_transfer')} />
                                    Transfer Bank
                                </label>
                                {data.payment_method === 'bank_transfer' && (
                                    <select
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        value={data.bank_id}
                                        onChange={(e) => setData('bank_id', Number(e.target.value))}
                                    >
                                        {banks.map((b) => (
                                            <option key={b.id} value={b.id}>{b.bankName} — {b.accountNumber}</option>
                                        ))}
                                    </select>
                                )}
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
                                <div className="border-t pt-2 space-y-1">
                                    <div className="flex justify-between">
                                        <span>Subtotal</span>
                                        <span>{formatRupiah(pricing.subtotal)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Ongkir</span>
                                        <span>{formatRupiah(shippingCost)}</span>
                                    </div>
                                    <div className="flex justify-between font-bold text-base pt-1">
                                        <span>Total</span>
                                        <span className="text-primary">{formatRupiah(grandTotal)}</span>
                                    </div>
                                </div>
                            </div>
                            <Button type="submit" className="w-full mt-4" disabled={processing}>
                                Buat Pesanan
                            </Button>
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
