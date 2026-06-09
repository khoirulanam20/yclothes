import { Head, Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageHeader } from '@/components/storefront/AccountPageHeader';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type PreviewItem = { productName: string; imageUrl?: string | null; qty: number };

type Order = {
    id: number;
    orderNumber: string;
    grandTotal: number;
    orderStatus: string;
    paymentStatus: string;
    paymentConfirmationStatus?: string;
    createdAt?: string;
    itemsCount?: number;
    previewItems?: PreviewItem[];
    canReview?: boolean;
};

type Props = { orders: Order[] };

export default function Orders({ orders }: Props) {
    return (
        <AccountLayout>
            <Head title="Pesanan Saya" />
            <AccountPageHeader title="Pesanan Saya" />
            {orders.length === 0 ? (
                <AccountPageShell title="Belum ada pesanan">
                    <p className="py-6 text-center text-muted-foreground">
                        Mulai belanja dan pesanan Anda akan muncul di sini.
                    </p>
                </AccountPageShell>
            ) : (
                <div className="space-y-4">
                    {orders.map((order) => {
                        const preview = order.previewItems ?? [];
                        const extraCount = Math.max(0, (order.itemsCount ?? preview.length) - preview.length);
                        const needsPayment = order.paymentStatus === 'pending'
                            && ['pending', 'confirmed', 'processing'].includes(order.orderStatus);

                        return (
                            <AccountPageShell key={order.id} noPadding className="overflow-hidden">
                                <div className="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/30 px-4 py-3 sm:px-5">
                                    <div className="min-w-0">
                                        <p className="text-xs text-muted-foreground">
                                            {order.createdAt && new Date(order.createdAt).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'long',
                                                year: 'numeric',
                                            })}
                                        </p>
                                        <p className="font-semibold">#{order.orderNumber}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Badge variant="secondary">
                                            {orderStatusLabels[order.orderStatus] ?? order.orderStatus}
                                        </Badge>
                                        <Badge variant="outline">
                                            {paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}
                                        </Badge>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                    <div className="flex min-w-0 flex-1 gap-3">
                                        <div className="flex shrink-0 gap-2">
                                            {preview.map((item, index) => (
                                                <div
                                                    key={`${order.id}-${index}`}
                                                    className="size-14 overflow-hidden rounded-lg border bg-muted sm:size-16"
                                                >
                                                    {item.imageUrl ? (
                                                        <img
                                                            src={item.imageUrl}
                                                            alt=""
                                                            className="size-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex size-full items-center justify-center text-[10px] text-muted-foreground">
                                                            No img
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                        <div className="min-w-0">
                                            {preview[0] && (
                                                <p className="line-clamp-2 text-sm font-medium">
                                                    {preview[0].productName}
                                                    {preview[0].qty > 1 && (
                                                        <span className="text-muted-foreground"> × {preview[0].qty}</span>
                                                    )}
                                                </p>
                                            )}
                                            {extraCount > 0 && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    +{extraCount} produk lain
                                                </p>
                                            )}
                                            {(order.itemsCount ?? preview.length) > 0 && extraCount === 0 && preview.length > 1 && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {order.itemsCount ?? preview.length} item
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 flex-wrap items-center gap-3 sm:flex-col sm:items-end">
                                        <p className="text-lg font-bold text-foreground">
                                            {formatRupiah(order.grandTotal)}
                                        </p>
                                        <div className="flex gap-2">
                                            {order.canReview && (
                                                <Button size="sm" asChild>
                                                    <Link href={`/account/orders/${order.id}#review-section`}>
                                                        Beri Ulasan
                                                    </Link>
                                                </Button>
                                            )}
                                            {needsPayment && (
                                                <Button size="sm" asChild>
                                                    <Link href={`/account/orders/${order.id}`}>Bayar</Link>
                                                </Button>
                                            )}
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/account/orders/${order.id}`}>Detail</Link>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </AccountPageShell>
                        );
                    })}
                </div>
            )}
        </AccountLayout>
    );
}
