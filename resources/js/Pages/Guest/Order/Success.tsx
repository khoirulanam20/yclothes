import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';

type Order = {
    orderNumber: string; grandTotal: number; paymentMethod: string;
    bankName?: string | null; bankAccountNumber?: string | null; bankAccountName?: string | null;
};
type Props = { order: Order };

export default function Success({ order }: Props) {
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
                        </div>
                    )}
                </SectionCard>

                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <Button asChild>
                        <Link href={`/order/${order.orderNumber}`}>Lihat Detail</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href="/products">Lanjut Belanja</Link>
                    </Button>
                </div>
            </PageContainer>
        </GuestLayout>
    );
}
