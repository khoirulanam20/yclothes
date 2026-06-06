import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import { CopyAmount } from '@/components/storefront/CopyAmount';
import { PaymentConfirmationDialog } from '@/components/storefront/PaymentConfirmationDialog';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string };
type OrderItem = {
    id: number; productName: string; qty: number; unitPrice: number; subtotal: number;
};
type TimelineEntry = { toStatus: string; note?: string | null; createdAt: string };
type Review = { orderItemId?: number; rating: number; isApproved: boolean };
type Order = {
    id: number; orderNumber: string; customerName: string; customerPhone: string;
    fullShippingAddress?: string; shippingAddress: string; shippingCity: string;
    shippingCost: number; totalPrice: number; taxAmount?: number; discountAmount?: number;
    grandTotal: number; uniquePaymentAmount?: number | null;
    paymentMethod: string; paymentStatus: string; orderStatus: string;
    isReplacement?: boolean;
    paymentConfirmationStatus?: string;
    courier?: string | null; trackingNumber?: string | null;
    bankName?: string | null; bankAccountNumber?: string | null; bankAccountName?: string | null;
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
};

export default function Show({
    order, timeline = [], reviews = [], canConfirmReceived, canConfirmPayment, banks = [],
    qris, canReturn, returnableItems = [], isAccountView, canReview = false, reviewsRequireLogin = false,
    codInstructions,
}: Props) {
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);

    const paymentSubmitUrl = isAccountView
        ? `/account/orders/${order.id}/confirm-payment`
        : `/order/${order.orderNumber}/confirm-payment`;

    const reviewForm = useForm({ order_item_id: order.items[0]?.id ?? 0, rating: 5, review: '' });

    const submitReview = (itemId: number) => {
        reviewForm.setData('order_item_id', itemId);
        reviewForm.post(isAccountView ? `/account/orders/${order.id}/reviews` : `/order/${order.orderNumber}/reviews`, {
            preserveScroll: true,
        });
    };

    const confirmReceived = () => {
        router.post(
            isAccountView ? `/account/orders/${order.id}/confirm-received` : `/order/${order.orderNumber}/confirm-received`,
            {},
            { preserveScroll: true },
        );
    };

    const showTransferInstructions = order.paymentMethod === 'bank_transfer' && order.paymentStatus !== 'paid';
    const showQrisInstructions = order.paymentMethod === 'qris' && order.paymentStatus !== 'paid';
    const showCodInstructions = order.paymentMethod === 'cod' && order.paymentStatus !== 'paid' && !!codInstructions;
    const isQris = order.paymentMethod === 'qris';

    return (
        <GuestLayout>
            <Head title={`Pesanan #${order.orderNumber}`} />
            <PageContainer narrow>
                <div className="flex justify-between items-center mb-4 flex-wrap gap-2">
                    <h1 className="text-xl font-bold">Pesanan #{order.orderNumber}</h1>
                    <div className="flex gap-2 flex-wrap">
                        {canReturn && returnableItems.length > 0 && (
                            <Button size="sm" variant="outline" asChild>
                                <Link href={`/account/orders/${order.id}/returns/create`}>Ajukan Retur</Link>
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
                        {canConfirmReceived && (
                            <Button size="sm" onClick={confirmReceived}>
                                Pesanan Diterima
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/order/track">Lacak Pesanan</Link>
                        </Button>
                    </div>
                </div>

                <div className="flex gap-2 mb-4 flex-wrap">
                    <Badge>{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge>
                    <Badge variant="outline">{paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}</Badge>
                    {order.isReplacement && (
                        <Badge variant="secondary">Pesanan Pengganti</Badge>
                    )}
                    {order.paymentConfirmationStatus === 'pending' && (
                        <Badge variant="secondary">Menunggu verifikasi pembayaran</Badge>
                    )}
                    {order.paymentConfirmationStatus === 'rejected' && (
                        <Badge variant="outline" className="border-destructive text-destructive">Konfirmasi pembayaran ditolak</Badge>
                    )}
                </div>

                {timeline.length > 0 && (
                    <SectionCard title="Timeline Status" className="mb-4">
                        <ol className="space-y-3 text-sm border-l-2 border-primary/20 pl-4">
                            {timeline.map((entry, i) => (
                                <li key={i}>
                                    <p className="font-medium">{orderStatusLabels[entry.toStatus] ?? entry.toStatus}</p>
                                    {entry.note && <p className="text-muted-foreground">{entry.note}</p>}
                                    <p className="text-xs text-muted-foreground">{new Date(entry.createdAt).toLocaleString('id-ID')}</p>
                                </li>
                            ))}
                        </ol>
                    </SectionCard>
                )}

                <SectionCard title="Detail Pengiriman" className="mb-4">
                    <div className="text-sm space-y-1">
                        <p>{order.customerName} — {order.customerPhone}</p>
                        <p className="text-muted-foreground">{order.fullShippingAddress ?? `${order.shippingAddress}, ${order.shippingCity}`}</p>
                        {order.courier && (
                            <p>Kurir: {order.courier}{order.trackingNumber && ` (Resi: ${order.trackingNumber})`}</p>
                        )}
                    </div>
                </SectionCard>

                <SectionCard title="Item Pesanan" noPadding className="mb-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead>Qty</TableHead>
                                <TableHead className="text-right">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {order.items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>{item.productName}</TableCell>
                                    <TableCell>{item.qty}</TableCell>
                                    <TableCell className="text-right">{formatRupiah(item.subtotal)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <div className="p-4 border-t space-y-1 text-sm">
                        <div className="flex justify-between"><span>Subtotal</span><span>{formatRupiah(order.totalPrice)}</span></div>
                        {(order.discountAmount ?? 0) > 0 && (
                            <div className="flex justify-between text-green-600"><span>Diskon</span><span>-{formatRupiah(order.discountAmount!)}</span></div>
                        )}
                        <div className="flex justify-between"><span>Ongkir</span><span>{formatRupiah(order.shippingCost)}</span></div>
                        <div className="flex justify-between font-bold text-base pt-1">
                            <span>Total</span><span className="text-primary">{formatRupiah(order.grandTotal)}</span>
                        </div>
                    </div>
                </SectionCard>

                {showQrisInstructions && qris && (
                    <SectionCard title="Instruksi QRIS" className="mb-4">
                        {qris.merchantName && (
                            <p className="text-sm font-medium mb-2">{qris.merchantName}</p>
                        )}
                        {qris.imageUrl && (
                            <img
                                src={qris.imageUrl}
                                alt="QRIS"
                                className="max-w-[220px] rounded border bg-white p-2 mb-3"
                            />
                        )}
                        {order.uniquePaymentAmount && (
                            <p className="text-sm font-semibold text-primary mb-2">
                                Bayar tepat: <CopyAmount amount={order.uniquePaymentAmount} />
                            </p>
                        )}
                        {qris.instructions && (
                            <p className="text-sm text-muted-foreground mb-2">{qris.instructions}</p>
                        )}
                        {order.paymentConfirmationStatus === 'pending' && (
                            <p className="text-sm text-muted-foreground mt-3">
                                Konfirmasi pembayaran sedang menunggu verifikasi penjual.
                            </p>
                        )}
                        {order.paymentConfirmationStatus === 'rejected' && canConfirmPayment && (
                            <p className="text-sm text-muted-foreground mt-3">
                                Konfirmasi sebelumnya ditolak. Silakan ajukan ulang via tombol di atas.
                            </p>
                        )}
                    </SectionCard>
                )}

                {showCodInstructions && (
                    <SectionCard title="Bayar di Tempat (COD)" className="mb-4">
                        <p className="text-sm text-muted-foreground whitespace-pre-line">{codInstructions}</p>
                        <p className="text-sm font-semibold text-primary mt-3">
                            Total bayar saat terima: {formatRupiah(order.grandTotal)}
                        </p>
                    </SectionCard>
                )}

                {showTransferInstructions && (
                    <SectionCard title="Instruksi Transfer" className="mb-4">
                        <p className="text-sm">{order.bankName} — {order.bankAccountNumber}</p>
                        <p className="text-sm text-muted-foreground mb-2">a.n. {order.bankAccountName}</p>
                        {order.uniquePaymentAmount && (
                            <p className="text-sm font-semibold text-primary">
                                Transfer tepat: <CopyAmount amount={order.uniquePaymentAmount} />
                            </p>
                        )}
                        {order.paymentConfirmationStatus === 'pending' && (
                            <p className="text-sm text-muted-foreground mt-3">
                                Konfirmasi pembayaran sedang menunggu verifikasi penjual.
                            </p>
                        )}
                        {order.paymentConfirmationStatus === 'rejected' && canConfirmPayment && (
                            <p className="text-sm text-muted-foreground mt-3">
                                Konfirmasi sebelumnya ditolak. Silakan ajukan ulang via tombol di atas.
                            </p>
                        )}
                    </SectionCard>
                )}

                {canReview && (
                    <SectionCard title="Rating Produk" className="mb-4">
                        <div className="space-y-4">
                            {order.items.map((item) => {
                                const existing = reviews.find((r) => r.orderItemId === item.id);
                                if (existing) {
                                    return (
                                        <div key={item.id} className="text-sm border rounded p-3">
                                            <p className="font-medium">{item.productName}</p>
                                            <p>Rating: {'★'.repeat(existing.rating)}{existing.isApproved ? '' : ' (menunggu moderasi)'}</p>
                                        </div>
                                    );
                                }
                                return (
                                    <div key={item.id} className="border rounded p-3 space-y-2">
                                        <p className="font-medium text-sm">{item.productName}</p>
                                        <div className="flex gap-2 items-center">
                                            <Label className="text-xs">Rating</Label>
                                            <select
                                                className="h-8 rounded border px-2 text-sm"
                                                value={reviewForm.data.rating}
                                                onChange={(e) => reviewForm.setData('rating', Number(e.target.value))}
                                            >
                                                {[5, 4, 3, 2, 1].map((n) => <option key={n} value={n}>{n} bintang</option>)}
                                            </select>
                                        </div>
                                        <Textarea
                                            rows={2}
                                            placeholder="Ulasan (opsional)"
                                            value={reviewForm.data.review}
                                            onChange={(e) => reviewForm.setData('review', e.target.value)}
                                        />
                                        <Button size="sm" onClick={() => submitReview(item.id)} disabled={reviewForm.processing}>
                                            Kirim Review
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                        {reviewsRequireLogin && !isAccountView && (
                            <p className="text-xs text-muted-foreground mt-3">
                                <Link href="/account/login" className="underline">Login</Link> untuk menyimpan ulasan ke akun Anda.
                            </p>
                        )}
                    </SectionCard>
                )}
            </PageContainer>
        </GuestLayout>
    );
}
