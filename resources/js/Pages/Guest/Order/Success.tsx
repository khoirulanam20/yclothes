import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { CopyAmount } from '@/components/storefront/CopyAmount';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';

type Order = {
    orderNumber: string; grandTotal: number; uniquePaymentAmount?: number | null; paymentMethod: string;
    bankName?: string | null; bankAccountNumber?: string | null; bankAccountName?: string | null;
};
type QrisSettings = {
    imageUrl?: string | null;
    merchantName?: string | null;
    instructions?: string | null;
};
type Props = { order: Order; orderShowUrl: string; qris?: QrisSettings | null; klikqrisPaymentUrl?: string | null };

export default function Success({ order, orderShowUrl, qris, klikqrisPaymentUrl }: Props) {
    return (
        <GuestLayout>
            <Head title="Pesanan Berhasil" />
            <PageContainer narrow className="text-center">
                <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary text-3xl mb-4">
                    ✓
                </div>
                <h1 className="text-2xl font-bold mb-2">Pesanan Berhasil!</h1>
                <p className="text-muted-foreground mb-6 text-sm">
                    Nomor pesanan: <strong>{order.orderNumber}</strong>
                </p>

                <SectionCard className="text-left mb-6">
                    <p className="font-semibold mb-2">Total: {formatRupiah(order.grandTotal)}</p>
                    {order.paymentMethod === 'bank_transfer' && order.bankName && (
                        <div className="text-sm space-y-1 text-muted-foreground">
                            <p>Silakan transfer ke:</p>
                            <p className="text-foreground">{order.bankName} — {order.bankAccountNumber}</p>
                            <p>a.n. {order.bankAccountName}</p>
                            {order.uniquePaymentAmount && (
                                <p className="text-primary font-semibold pt-1">
                                    Nominal unik: <CopyAmount amount={order.uniquePaymentAmount} />
                                </p>
                            )}
                            <p className="pt-2">Setelah transfer, konfirmasi pembayaran di halaman detail pesanan.</p>
                        </div>
                    )}
                    {order.paymentMethod === 'qris' && qris && (
                        <div className="text-sm space-y-3 text-muted-foreground">
                            {qris.merchantName && (
                                <p className="text-foreground font-medium">{qris.merchantName}</p>
                            )}
                            {qris.imageUrl && (
                                <img
                                    src={qris.imageUrl}
                                    alt="QRIS"
                                    className="mx-auto max-w-[220px] rounded border bg-white p-2"
                                />
                            )}
                            {order.uniquePaymentAmount && (
                                <p className="text-primary font-semibold">
                                    Nominal unik: <CopyAmount amount={order.uniquePaymentAmount} />
                                </p>
                            )}
                            <p className="text-foreground">{qris.instructions}</p>
                            <p>Setelah bayar, konfirmasi pembayaran di halaman detail pesanan.</p>
                        </div>
                    )}
                    {(order.paymentMethod === 'midtrans' || order.paymentMethod === 'doku') && (
                        <p className="text-sm text-muted-foreground">
                            Selesaikan pembayaran online melalui halaman gateway yang dibuka, atau cek status di detail pesanan.
                        </p>
                    )}
                    {order.paymentMethod === 'klikqris' && (
                        <div className="text-sm space-y-2 text-muted-foreground">
                            {order.uniquePaymentAmount && (
                                <p className="text-primary font-semibold">
                                    Nominal bayar: <CopyAmount amount={order.uniquePaymentAmount} />
                                </p>
                            )}
                            <p>Selesaikan pembayaran QRIS melalui popup KlikQRIS.</p>
                            {klikqrisPaymentUrl && (
                                <Button asChild size="sm" className="mt-2">
                                    <a href={klikqrisPaymentUrl}>Bayar dengan KlikQRIS</a>
                                </Button>
                            )}
                        </div>
                    )}
                </SectionCard>

                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <Button asChild>
                        <Link href={orderShowUrl}>Lihat Detail</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/products">Lanjut Belanja</Link>
                    </Button>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
