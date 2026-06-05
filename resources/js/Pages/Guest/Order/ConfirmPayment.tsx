import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { CopyAmount } from '@/components/storefront/CopyAmount';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FieldError } from '@/components/admin/FieldError';
import { formatRupiah } from '@/lib/utils';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string };
type Order = {
    orderNumber: string; grandTotal: number; uniquePaymentAmount?: number | null;
    bankName?: string | null; bankAccountNumber?: string | null; bankAccountName?: string | null;
};
type Props = { order: Order; banks: Bank[] };

export default function ConfirmPayment({ order, banks }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        payment_bank_id: banks[0]?.id ?? '',
        amount_claimed: order.uniquePaymentAmount ?? order.grandTotal,
        transfer_date: new Date().toISOString().slice(0, 10),
        sender_name: '',
        proof_image: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/order/${order.orderNumber}/confirm-payment`, { forceFormData: true });
    };

    return (
        <GuestLayout>
            <Head title="Konfirmasi Pembayaran" />
            <PageContainer narrow>
                <h1 className="text-xl font-bold mb-4">Konfirmasi Pembayaran</h1>
                <p className="text-sm text-muted-foreground mb-4">Pesanan #{order.orderNumber}</p>

                <SectionCard className="mb-4">
                    <p className="text-sm">Transfer ke: <strong>{order.bankName}</strong> — {order.bankAccountNumber}</p>
                    <p className="text-sm text-muted-foreground">a.n. {order.bankAccountName}</p>
                    {order.uniquePaymentAmount && (
                        <p className="text-primary font-semibold mt-2">
                            Nominal unik: <CopyAmount amount={order.uniquePaymentAmount} />
                        </p>
                    )}
                </SectionCard>

                <form onSubmit={submit}>
                    <SectionCard title="Form Konfirmasi">
                        <div className="space-y-3">
                            <div>
                                <Label>Rekening Tujuan</Label>
                                <select
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={data.payment_bank_id}
                                    onChange={(e) => setData('payment_bank_id', Number(e.target.value))}
                                >
                                    {banks.map((b) => (
                                        <option key={b.id} value={b.id}>{b.bankName} — {b.accountNumber}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Jumlah Dibayar</Label>
                                <Input type="number" value={data.amount_claimed} onChange={(e) => setData('amount_claimed', Number(e.target.value))} required />
                                <FieldError message={errors.amount_claimed} />
                            </div>
                            <div>
                                <Label>Tanggal Transfer</Label>
                                <Input type="date" value={data.transfer_date} onChange={(e) => setData('transfer_date', e.target.value)} required />
                            </div>
                            <div>
                                <Label>Nama Pengirim</Label>
                                <Input value={data.sender_name} onChange={(e) => setData('sender_name', e.target.value)} required />
                                <FieldError message={errors.sender_name} />
                            </div>
                            <div>
                                <Label>Bukti Transfer (opsional)</Label>
                                <Input type="file" accept="image/*" onChange={(e) => setData('proof_image', e.target.files?.[0] ?? null)} />
                            </div>
                            <Button type="submit" disabled={processing} className="w-full">Kirim Konfirmasi</Button>
                            <Button variant="outline" className="w-full" asChild>
                                <Link href={`/order/${order.orderNumber}`}>Kembali</Link>
                            </Button>
                        </div>
                    </SectionCard>
                </form>
            </PageContainer>
        </GuestLayout>
    );
}
