import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Package, Star, Truck } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';
import AccountLayout from '@/Layouts/AccountLayout';
import { CopyAmount } from '@/components/storefront/CopyAmount';
import { AccountPageHeader } from '@/components/storefront/AccountPageHeader';
import { OrderStatusOverview } from '@/components/storefront/OrderStatusOverview';
import { OrderTimeline } from '@/components/storefront/OrderTimeline';
import { PaymentConfirmationDialog } from '@/components/storefront/PaymentConfirmationDialog';
import { PageContainer } from '@/components/storefront/PageContainer';
import { ReviewItemForm } from '@/components/storefront/ReviewItemForm';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels } from '@/lib/order-status';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string };
type OrderItem = {
    id: number;
    productName: string;
    qty: number;
    unitPrice: number;
    subtotal: number;
    size?: string | null;
    color?: string | null;
    imageUrl?: string | null;
};
type TimelineEntry = { toStatus: string; note?: string | null; createdAt: string };
type Review = { orderItemId?: number; rating: number; isApproved: boolean; imagesUrl?: string[] };
type Order = {
    id: number;
    orderNumber: string;
    customerName: string;
    customerPhone: string;
    fullShippingAddress?: string;
    shippingAddress: string;
    shippingCity: string;
    shippingCost: number;
    totalPrice: number;
    taxAmount?: number;
    discountAmount?: number;
    grandTotal: number;
    uniquePaymentAmount?: number | null;
    paymentMethod: string;
    paymentStatus: string;
    orderStatus: string;
    completedAt?: string | null;
    isReplacement?: boolean;
    paymentConfirmationStatus?: string;
    courier?: string | null;
    trackingNumber?: string | null;
    bankName?: string | null;
    bankAccountNumber?: string | null;
    bankAccountName?: string | null;
    items: OrderItem[];
};

type QrisSettings = {
    imageUrl?: string | null;
    merchantName?: string | null;
    instructions?: string | null;
};

type Props = {
    order: Order;
    timeline?: TimelineEntry[];
    reviews?: Review[];
    canConfirmReceived?: boolean;
    canConfirmPayment?: boolean;
    banks?: Bank[];
    qris?: QrisSettings | null;
    canReturn?: boolean;
    returnableItems?: { id: number; productName: string; qty: number }[];
    isAccountView?: boolean;
    canReview?: boolean;
    reviewsRequireLogin?: boolean;
    codInstructions?: string | null;
    klikqrisPaymentUrl?: string | null;
};

export default function Show({
    order,
    timeline = [],
    reviews = [],
    canConfirmReceived,
    canConfirmPayment,
    banks = [],
    qris,
    canReturn,
    returnableItems = [],
    isAccountView,
    canReview = false,
    reviewsRequireLogin = false,
    codInstructions,
    klikqrisPaymentUrl,
}: Props) {
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);

    const paymentSubmitUrl = isAccountView
        ? `/account/orders/${order.id}/confirm-payment`
        : `/order/${order.orderNumber}/confirm-payment`;

    const reviewSubmitUrl = isAccountView
        ? `/account/orders/${order.id}/reviews`
        : `/order/${order.orderNumber}/reviews`;

    const confirmReceived = () => {
        router.post(
            isAccountView ? `/account/orders/${order.id}/confirm-received` : `/order/${order.orderNumber}/confirm-received`,
            {},
            { preserveScroll: true },
        );
    };

    const showTransferInstructions = order.paymentMethod === 'bank_transfer' && order.paymentStatus !== 'paid';
    const showQrisInstructions = order.paymentMethod === 'qris' && order.paymentStatus !== 'paid';
    const showKlikQrisInstructions = order.paymentMethod === 'klikqris' && order.paymentStatus !== 'paid';
    const showCodInstructions = order.paymentMethod === 'cod' && order.paymentStatus !== 'paid' && !!codInstructions;
    const isQris = order.paymentMethod === 'qris';
    const canSubmitReturn = !!(canReturn && returnableItems.length > 0);
    const needsAction = canConfirmPayment || canConfirmReceived || canSubmitReturn;
    const orderReceived = order.orderStatus === 'completed' && !!order.completedAt;

    const orderContent = (
        <>
            {!isAccountView && (
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p className="text-sm text-muted-foreground">Detail Pesanan</p>
                        <h1 className="text-2xl font-bold">No. Pesanan: {order.orderNumber}</h1>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/order/track">Lacak Pesanan</Link>
                    </Button>
                </div>
            )}

            {isAccountView && (
                <AccountPageHeader
                    title={`Pesanan #${order.orderNumber}`}
                    action={
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/account/orders">Kembali ke Pesanan</Link>
                        </Button>
                    }
                />
            )}

            <OrderStatusOverview
                orderStatus={order.orderStatus}
                paymentStatus={order.paymentStatus}
                isReplacement={order.isReplacement}
            />

                {needsAction && (
                    <AccountPageShell title="Perlu Tindakan" className="mb-6 border-primary/20 bg-primary/5">
                        <div className="flex flex-wrap gap-2">
                            {canConfirmReceived && (
                                <Button size="sm" onClick={confirmReceived}>
                                    Pesanan Diterima
                                </Button>
                            )}
                            {canConfirmPayment && (
                                <>
                                    <Button size="sm" onClick={() => setPaymentModalOpen(true)}>
                                        Konfirmasi Pembayaran
                                    </Button>
                                    <PaymentConfirmationDialog
                                        open={paymentModalOpen}
                                        onOpenChange={setPaymentModalOpen}
                                        order={order}
                                        banks={banks}
                                        submitUrl={paymentSubmitUrl}
                                        isQris={isQris}
                                        qris={qris}
                                    />
                                </>
                            )}
                            {canSubmitReturn && (
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={`/account/orders/${order.id}/returns/create`}>Ajukan Retur</Link>
                                </Button>
                            )}
                        </div>
                        {order.paymentConfirmationStatus === 'pending' && (
                            <p className="mt-3 text-sm text-muted-foreground">
                                Konfirmasi pembayaran sedang menunggu verifikasi penjual.
                            </p>
                        )}
                        {order.paymentConfirmationStatus === 'rejected' && (
                            <p className="mt-3 text-sm text-destructive">
                                Konfirmasi pembayaran sebelumnya ditolak. Silakan ajukan ulang.
                            </p>
                        )}
                    </AccountPageShell>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {(showTransferInstructions || showQrisInstructions || showKlikQrisInstructions || showCodInstructions) && (
                            <AccountPageShell title="Instruksi Pembayaran">
                                {showKlikQrisInstructions && (
                                    <div className="space-y-3">
                                        {order.uniquePaymentAmount && (
                                            <p className="text-sm font-semibold text-primary">
                                                Nominal bayar: <CopyAmount amount={order.uniquePaymentAmount} />
                                            </p>
                                        )}
                                        <p className="text-sm text-muted-foreground">
                                            Klik tombol di bawah untuk membuka popup pembayaran QRIS KlikQRIS.
                                        </p>
                                        {klikqrisPaymentUrl && (
                                            <Button asChild size="sm">
                                                <a href={klikqrisPaymentUrl}>Bayar dengan KlikQRIS</a>
                                            </Button>
                                        )}
                                    </div>
                                )}
                                {showQrisInstructions && qris && (
                                    <div className="space-y-3">
                                        {qris.merchantName && <p className="text-sm font-medium">{qris.merchantName}</p>}
                                        {qris.imageUrl && (
                                            <img
                                                src={qris.imageUrl}
                                                alt="QRIS"
                                                className="max-w-[220px] rounded border bg-white p-2"
                                            />
                                        )}
                                        {order.uniquePaymentAmount && (
                                            <p className="text-sm font-semibold text-primary">
                                                Bayar tepat: <CopyAmount amount={order.uniquePaymentAmount} />
                                            </p>
                                        )}
                                        {qris.instructions && (
                                            <p className="text-sm text-muted-foreground">{qris.instructions}</p>
                                        )}
                                    </div>
                                )}
                                {showCodInstructions && (
                                    <div className="space-y-2">
                                        <p className="text-sm text-muted-foreground whitespace-pre-line">{codInstructions}</p>
                                        <p className="text-sm font-semibold text-primary">
                                            Total bayar saat terima: {formatRupiah(order.grandTotal)}
                                        </p>
                                    </div>
                                )}
                                {showTransferInstructions && (
                                    <div className="space-y-2">
                                        <p className="text-sm">{order.bankName} — {order.bankAccountNumber}</p>
                                        <p className="text-sm text-muted-foreground">a.n. {order.bankAccountName}</p>
                                        {order.uniquePaymentAmount && (
                                            <p className="text-sm font-semibold text-primary">
                                                Transfer tepat: <CopyAmount amount={order.uniquePaymentAmount} />
                                            </p>
                                        )}
                                    </div>
                                )}
                            </AccountPageShell>
                        )}

                        <AccountPageShell title={`Item Pesanan (${order.items.length})`} noPadding>
                            <div className="divide-y">
                                {order.items.map((item) => (
                                    <div key={item.id} className="flex gap-4 p-4">
                                        <div className="size-16 shrink-0 overflow-hidden rounded-lg border bg-muted sm:size-20">
                                            {item.imageUrl ? (
                                                <img src={item.imageUrl} alt="" className="size-full object-cover" />
                                            ) : (
                                                <div className="flex size-full items-center justify-center">
                                                    <Package className="size-6 text-muted-foreground/50" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium leading-snug">{item.productName}</p>
                                            {(item.size || item.color) && (
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {[item.size, item.color].filter(Boolean).join(' · ')}
                                                </p>
                                            )}
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {formatRupiah(item.unitPrice)} × {item.qty}
                                            </p>
                                        </div>
                                        <p className="shrink-0 text-sm font-semibold">{formatRupiah(item.subtotal)}</p>
                                    </div>
                                ))}
                            </div>
                        </AccountPageShell>

                        <AccountPageShell title="Ringkasan Pembayaran">
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Subtotal</span>
                                    <span>{formatRupiah(order.totalPrice)}</span>
                                </div>
                                {(order.discountAmount ?? 0) > 0 && (
                                    <div className="flex justify-between text-green-600">
                                        <span>Diskon</span>
                                        <span>-{formatRupiah(order.discountAmount!)}</span>
                                    </div>
                                )}
                                {(order.taxAmount ?? 0) > 0 && (
                                    <div className="flex justify-between">
                                        <span>Pajak</span>
                                        <span>{formatRupiah(order.taxAmount!)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Ongkir</span>
                                    <span>{formatRupiah(order.shippingCost)}</span>
                                </div>
                                <div className="flex justify-between border-t pt-3 text-base font-bold">
                                    <span>Total</span>
                                    <span className="text-primary">{formatRupiah(order.grandTotal)}</span>
                                </div>
                            </div>
                        </AccountPageShell>

                        <AccountPageShell title="Detail Pengiriman">
                            <div className="space-y-2 text-sm">
                                <p className="font-medium">{order.customerName}</p>
                                <p className="text-muted-foreground">{order.customerPhone}</p>
                                <p className="text-muted-foreground leading-relaxed">
                                    {order.fullShippingAddress ?? `${order.shippingAddress}, ${order.shippingCity}`}
                                </p>
                                {order.courier && (
                                    <p className="flex items-center gap-1.5 pt-1">
                                        <Truck className="size-4 text-muted-foreground" />
                                        {order.courier}
                                        {order.trackingNumber && ` · Resi ${order.trackingNumber}`}
                                    </p>
                                )}
                            </div>
                        </AccountPageShell>

                        {canReview && orderReceived && (
                            <div id="review-section">
                            <AccountPageShell title="Beri Ulasan Produk">
                                <div className="space-y-4">
                                    {order.items.map((item) => {
                                        const existing = reviews.find((r) => r.orderItemId === item.id);
                                        if (existing) {
                                            return (
                                                <div key={item.id} className="rounded-xl border bg-muted/20 p-4 text-sm">
                                                    <p className="font-medium">{item.productName}</p>
                                                    <div className="mt-1 flex items-center gap-1">
                                                        {Array.from({ length: existing.rating }).map((_, i) => (
                                                            <Star key={i} className="size-3.5 fill-amber-400 text-amber-400" />
                                                        ))}
                                                    </div>
                                                    {existing.imagesUrl && existing.imagesUrl.length > 0 && (
                                                        <div className="mt-2 flex flex-wrap gap-2">
                                                            {existing.imagesUrl.map((url, index) => (
                                                                <img
                                                                    key={`${url}-${index}`}
                                                                    src={url}
                                                                    alt=""
                                                                    className="size-14 rounded-md border object-cover"
                                                                />
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        }

                                        return (
                                            <ReviewItemForm
                                                key={item.id}
                                                itemId={item.id}
                                                productName={item.productName}
                                                submitUrl={reviewSubmitUrl}
                                            />
                                        );
                                    })}
                                </div>
                                {reviewsRequireLogin && !isAccountView && (
                                    <p className="mt-4 text-xs text-muted-foreground">
                                        <Link href="/account/login" className="underline">Login</Link> untuk menyimpan ulasan ke akun Anda.
                                    </p>
                                )}
                            </AccountPageShell>
                            </div>
                        )}
                    </div>

                    <div className="lg:col-span-1">
                        <div className="lg:sticky lg:top-24">
                            <OrderTimeline entries={timeline} />
                        </div>
                    </div>
                </div>
        </>
    );

    if (isAccountView) {
        return (
            <AccountLayout>
                <Head title={`Pesanan #${order.orderNumber}`} />
                {orderContent}
            </AccountLayout>
        );
    }

    return (
        <GuestLayout>
            <Head title={`Pesanan #${order.orderNumber}`} />
            <PageContainer>
                {orderContent}
            </PageContainer>
        </GuestLayout>
    );
}
