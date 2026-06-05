import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { CopyAmount } from '@/components/storefront/CopyAmount';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { useAdminConfirm } from '@/components/admin/AdminConfirmProvider';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type PaymentConfirmation = {
    id: number; amountClaimed: number; transferDate?: string; senderName?: string;
    proofImageUrl?: string | null; status: string; adminNote?: string | null;
    bank?: { bankName: string; accountNumber: string } | null;
};
type TimelineEntry = { toStatus: string; note?: string | null; createdAt: string };
type OrderItem = { id: number; productName: string; qty: number; unitPrice: number; subtotal: number; size?: string | null; color?: string | null };
type Order = {
    id: number; orderNumber: string; customerName: string; customerPhone: string; customerEmail: string;
    fullShippingAddress?: string; shippingAddress: string; shippingCity: string;
    shippingCost: number; totalPrice: number; taxAmount: number; discountAmount: number;
    grandTotal: number; uniquePaymentAmount?: number | null;
    paymentMethod: string; paymentStatus: string; orderStatus: string;
    courier?: string | null; courierService?: string | null; trackingNumber?: string | null;
    notes?: string | null; items: OrderItem[];
};
type Props = {
    order: Order;
    timeline?: TimelineEntry[];
    paymentConfirmations?: PaymentConfirmation[];
    allowedTransitions?: string[];
};

export default function Show({ order, timeline = [], paymentConfirmations = [], allowedTransitions = [] }: Props) {
    const confirm = useAdminConfirm();
    const [shipOpen, setShipOpen] = useState(false);
    const [proofUrl, setProofUrl] = useState<string | null>(null);

    const shipForm = useForm({
        courier: order.courier ?? '',
        courier_service: order.courierService ?? '',
        tracking_number: order.trackingNumber ?? '',
    });

    const submitShip = (e: React.FormEvent) => {
        e.preventDefault();
        shipForm.post(`/admin/orders/${order.id}/ship`, {
            preserveScroll: true,
            onSuccess: () => {
                setShipOpen(false);
                shipForm.reset();
            },
        });
    };

    const approveConfirmation = async (id: number) => {
        const ok = await confirm({ title: 'Setujui Konfirmasi', description: 'Verifikasi pembayaran dari pembeli?' });
        if (ok) router.post(`/admin/payment-confirmations/${id}/approve`, {}, { preserveScroll: true });
    };

    const transitionStatus = async (toStatus: string) => {
        const label = orderStatusLabels[toStatus] ?? toStatus;
        const ok = await confirm({
            title: 'Ubah Status',
            description: `Ubah status pesanan menjadi "${label}"?`,
        });
        if (!ok) return;

        router.post(`/admin/orders/${order.id}/status`, {
            order_status: toStatus,
            courier: order.courier ?? '',
            courier_service: order.courierService ?? '',
            tracking_number: order.trackingNumber ?? '',
            notes: order.notes ?? '',
        }, { preserveScroll: true });
    };

    return (
        <AdminLayout title={`Pesanan #${order.orderNumber}`} breadcrumbs={[{ label: 'Pesanan', href: '/admin/orders' }, { label: `#${order.orderNumber}` }]}>
            <Head title={`Pesanan #${order.orderNumber}`} />
            <AdminPageHeader
                title={`Pesanan #${order.orderNumber}`}
                backHref="/admin/orders"
                actions={
                    <Button size="sm" variant="outline" asChild>
                        <a href={`/admin/orders/${order.id}/invoice`} target="_blank" rel="noreferrer">Faktur</a>
                    </Button>
                }
            />

            <div className="grid lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card><CardHeader><CardTitle>Data Pemesan</CardTitle></CardHeader><CardContent className="grid sm:grid-cols-2 gap-2 text-sm">
                        <div><span className="text-muted-foreground">Nama:</span> {order.customerName}</div>
                        <div><span className="text-muted-foreground">WA:</span> {order.customerPhone}</div>
                        <div><span className="text-muted-foreground">Email:</span> {order.customerEmail}</div>
                        <div className="sm:col-span-2"><span className="text-muted-foreground">Alamat:</span> {order.fullShippingAddress ?? `${order.shippingAddress}, ${order.shippingCity}`}</div>
                    </CardContent></Card>

                    <Card><CardHeader><CardTitle>Produk ({order.items.length})</CardTitle></CardHeader><CardContent className="p-0">
                        <Table><TableHeader><TableRow><TableHead>Produk</TableHead><TableHead>Qty</TableHead><TableHead>Harga</TableHead><TableHead className="text-right">Subtotal</TableHead></TableRow></TableHeader>
                            <TableBody>{order.items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>{item.productName}{item.size && ` / ${item.size}`}{item.color && ` / ${item.color}`}</TableCell>
                                    <TableCell>{item.qty}</TableCell>
                                    <TableCell>{formatRupiah(item.unitPrice)}</TableCell>
                                    <TableCell className="text-right">{formatRupiah(item.subtotal)}</TableCell>
                                </TableRow>
                            ))}</TableBody></Table>
                        <div className="p-4 space-y-1 text-sm border-t">
                            <div className="flex justify-between"><span>Subtotal</span><span>{formatRupiah(order.totalPrice)}</span></div>
                            <div className="flex justify-between"><span>Ongkir</span><span>{formatRupiah(order.shippingCost)}</span></div>
                            {order.discountAmount > 0 && <div className="flex justify-between text-green-600"><span>Diskon</span><span>-{formatRupiah(order.discountAmount)}</span></div>}
                            {order.uniquePaymentAmount && order.paymentStatus !== 'paid' && (
                                <div className="flex justify-between text-primary items-center">
                                    <span>Nominal Unik</span>
                                    <CopyAmount amount={order.uniquePaymentAmount} />
                                </div>
                            )}
                            <div className="flex justify-between font-bold text-lg pt-2"><span>Grand Total</span><span className="text-primary">{formatRupiah(order.grandTotal)}</span></div>
                        </div>
                    </CardContent></Card>

                    {timeline.length > 0 && (
                        <Card><CardHeader><CardTitle>Timeline</CardTitle></CardHeader><CardContent>
                            <ol className="space-y-2 text-sm">
                                {timeline.map((entry, i) => (
                                    <li key={i} className="border-l-2 pl-3 border-muted">
                                        <span className="font-medium">{orderStatusLabels[entry.toStatus] ?? entry.toStatus}</span>
                                        {entry.note && <span className="text-muted-foreground"> — {entry.note}</span>}
                                        <div className="text-xs text-muted-foreground">{new Date(entry.createdAt).toLocaleString('id-ID')}</div>
                                    </li>
                                ))}
                            </ol>
                        </CardContent></Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card><CardHeader><CardTitle>Status</CardTitle></CardHeader><CardContent className="space-y-3">
                        <div className="flex justify-between"><span>Pesanan</span><Badge>{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge></div>
                        <div className="flex justify-between"><span>Pembayaran</span><Badge variant="outline">{paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}</Badge></div>
                        {(order.courier || order.trackingNumber) && (
                            <div className="text-sm space-y-1 pt-1 border-t">
                                {order.courier && <p><span className="text-muted-foreground">Kurir:</span> {order.courier}{order.courierService && ` · ${order.courierService}`}</p>}
                                {order.trackingNumber && <p><span className="text-muted-foreground">Resi:</span> {order.trackingNumber}</p>}
                            </div>
                        )}
                        {allowedTransitions.length > 0 && (
                            <div className="flex flex-col gap-2 pt-1">
                                {allowedTransitions.map((status) => (
                                    status === 'shipped' ? (
                                        <Button
                                            key={status}
                                            className="w-full"
                                            onClick={() => setShipOpen(true)}
                                        >
                                            {orderStatusLabels[status] ?? status}
                                        </Button>
                                    ) : (
                                        <Button
                                            key={status}
                                            variant={status === 'cancelled' ? 'destructive' : 'outline'}
                                            className="w-full"
                                            onClick={() => transitionStatus(status)}
                                        >
                                            {orderStatusLabels[status] ?? status}
                                        </Button>
                                    )
                                ))}
                            </div>
                        )}
                    </CardContent></Card>

                    {paymentConfirmations.length > 0 && (
                        <Card><CardHeader><CardTitle>Konfirmasi Pembayaran</CardTitle></CardHeader><CardContent className="space-y-3">
                            {paymentConfirmations.map((pc) => (
                                <div key={pc.id} className="border rounded p-3 text-sm space-y-2">
                                    <p>{pc.senderName} — {formatRupiah(pc.amountClaimed)}</p>
                                    <p className="text-muted-foreground">{pc.transferDate} · {pc.status}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {pc.proofImageUrl && (
                                            <Button type="button" size="sm" variant="outline" onClick={() => setProofUrl(pc.proofImageUrl!)}>
                                                Lihat Bukti
                                            </Button>
                                        )}
                                        {pc.status === 'pending' && (
                                            <Button type="button" size="sm" onClick={() => approveConfirmation(pc.id)}>
                                                Setujui
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </CardContent></Card>
                    )}
                </div>
            </div>

            <Dialog open={shipOpen} onOpenChange={setShipOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Data Pengiriman</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitShip} className="space-y-3">
                        <div>
                            <Label htmlFor="courier">Kurir</Label>
                            <Input id="courier" value={shipForm.data.courier} onChange={(e) => shipForm.setData('courier', e.target.value)} required />
                        </div>
                        <div>
                            <Label htmlFor="courier_service">Layanan</Label>
                            <Input id="courier_service" value={shipForm.data.courier_service} onChange={(e) => shipForm.setData('courier_service', e.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="tracking_number">No. Resi</Label>
                            <Input id="tracking_number" value={shipForm.data.tracking_number} onChange={(e) => shipForm.setData('tracking_number', e.target.value)} required />
                        </div>
                        <div className="flex gap-2 justify-end">
                            <Button type="button" variant="outline" onClick={() => setShipOpen(false)}>Batal</Button>
                            <Button type="submit" disabled={shipForm.processing}>Simpan & Tandai Dikirim</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!proofUrl} onOpenChange={(open) => !open && setProofUrl(null)}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Bukti Transfer</DialogTitle>
                    </DialogHeader>
                    {proofUrl && (
                        <img src={proofUrl} alt="Bukti transfer" className="w-full max-h-[70vh] object-contain rounded" />
                    )}
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
