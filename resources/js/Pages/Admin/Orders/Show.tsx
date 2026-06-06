import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent } from '@/components/admin/AdminContent';
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
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { ORDER_FLOW_STEPS, type OrderAction, isInteractiveAction } from '@/lib/order-actions';
import { orderStatusLabels, paymentMethodLabels, paymentStatusLabels } from '@/lib/order-status';

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
type ActiveReturn = { id: number; requestNumber: string } | null;

type Props = {
    order: Order;
    timeline?: TimelineEntry[];
    paymentConfirmations?: PaymentConfirmation[];
    orderActions?: OrderAction[];
    flowStep?: number;
    activeReturnRequest?: ActiveReturn;
};

export default function Show({
    order,
    timeline = [],
    paymentConfirmations = [],
    orderActions = [],
    flowStep = 1,
    activeReturnRequest = null,
}: Props) {
    const confirm = useAdminConfirm();
    const [shipOpen, setShipOpen] = useState(false);
    const [proofUrl, setProofUrl] = useState<string | null>(null);
    const [rejectOpen, setRejectOpen] = useState(false);
    const [rejectConfirmationId, setRejectConfirmationId] = useState<number | null>(null);

    const rejectForm = useForm({ admin_note: '' });

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

    const submitReject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!rejectConfirmationId) return;
        rejectForm.post(`/admin/payment-confirmations/${rejectConfirmationId}/reject`, {
            preserveScroll: true,
            onSuccess: () => {
                setRejectOpen(false);
                setRejectConfirmationId(null);
                rejectForm.reset();
            },
        });
    };

    const handleAction = async (action: OrderAction) => {
        switch (action.key) {
            case 'verify_payment': {
                const ok = await confirm({
                    title: 'Verifikasi Pembayaran',
                    description: 'Tandai pesanan ini sebagai lunas tanpa konfirmasi form pembeli?',
                });
                if (ok) router.post(`/admin/orders/${order.id}/payment`, {}, { preserveScroll: true });
                break;
            }
            case 'approve_confirmation': {
                const ok = await confirm({
                    title: 'Setujui Konfirmasi',
                    description: 'Verifikasi pembayaran dari pembeli?',
                });
                if (ok && action.confirmationId) {
                    router.post(`/admin/payment-confirmations/${action.confirmationId}/approve`, {}, { preserveScroll: true });
                }
                break;
            }
            case 'reject_confirmation':
                if (action.confirmationId) {
                    setRejectConfirmationId(action.confirmationId);
                    setRejectOpen(true);
                }
                break;
            case 'process': {
                const ok = await confirm({
                    title: 'Proses Pesanan',
                    description: 'Mulai memproses pesanan ini?',
                });
                if (ok) {
                    router.post(`/admin/orders/${order.id}/status`, { order_status: 'processed' }, { preserveScroll: true });
                }
                break;
            }
            case 'ship':
                setShipOpen(true);
                break;
            case 'cancel': {
                const ok = await confirm({
                    title: 'Batalkan Pesanan',
                    description: 'Pesanan akan dibatalkan. Lanjutkan?',
                    variant: 'destructive',
                });
                if (ok) {
                    router.post(`/admin/orders/${order.id}/status`, { order_status: 'cancelled' }, { preserveScroll: true });
                }
                break;
            }
            default:
                break;
        }
    };

    const infoActions = orderActions.filter((a) => a.key === 'info' && a.hint);
    const interactiveActions = orderActions.filter(isInteractiveAction);

    return (
        <AdminLayout
            title={`Pesanan #${order.orderNumber}`}
            breadcrumbs={[{ label: 'Pesanan', href: '/admin/orders' }, { label: `#${order.orderNumber}` }]}
        >
            <Head title={`Pesanan #${order.orderNumber}`} />

            <AdminContent>
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
                        <Card>
                            <CardHeader><CardTitle>Data Pemesan</CardTitle></CardHeader>
                            <CardContent className="grid sm:grid-cols-2 gap-2 text-sm">
                                <div><span className="text-muted-foreground">Nama:</span> {order.customerName}</div>
                                <div><span className="text-muted-foreground">WA:</span> {order.customerPhone}</div>
                                <div><span className="text-muted-foreground">Email:</span> {order.customerEmail}</div>
                                <div className="sm:col-span-2">
                                    <span className="text-muted-foreground">Alamat:</span>{' '}
                                    {order.fullShippingAddress ?? `${order.shippingAddress}, ${order.shippingCity}`}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Produk ({order.items.length})</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Produk</TableHead>
                                            <TableHead>Qty</TableHead>
                                            <TableHead>Harga</TableHead>
                                            <TableHead className="text-right">Subtotal</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {order.items.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    {item.productName}
                                                    {item.size && ` / ${item.size}`}
                                                    {item.color && ` / ${item.color}`}
                                                </TableCell>
                                                <TableCell>{item.qty}</TableCell>
                                                <TableCell>{formatRupiah(item.unitPrice)}</TableCell>
                                                <TableCell className="text-right">{formatRupiah(item.subtotal)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                <div className="p-4 space-y-1 text-sm border-t">
                                    <div className="flex justify-between"><span>Subtotal</span><span>{formatRupiah(order.totalPrice)}</span></div>
                                    <div className="flex justify-between"><span>Ongkir</span><span>{formatRupiah(order.shippingCost)}</span></div>
                                    {order.discountAmount > 0 && (
                                        <div className="flex justify-between text-green-600">
                                            <span>Diskon</span><span>-{formatRupiah(order.discountAmount)}</span>
                                        </div>
                                    )}
                                    {order.uniquePaymentAmount && order.paymentStatus !== 'paid' && (
                                        <div className="flex justify-between text-primary items-center">
                                            <span>Nominal Unik</span>
                                            <CopyAmount amount={order.uniquePaymentAmount} />
                                        </div>
                                    )}
                                    <div className="flex justify-between font-bold text-lg pt-2">
                                        <span>Grand Total</span>
                                        <span className="text-primary">{formatRupiah(order.grandTotal)}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {timeline.length > 0 && (
                            <Card>
                                <CardHeader><CardTitle>Timeline</CardTitle></CardHeader>
                                <CardContent>
                                    <ol className="space-y-2 text-sm">
                                        {timeline.map((entry, i) => (
                                            <li key={i} className="border-l-2 pl-3 border-muted">
                                                <span className="font-medium">
                                                    {orderStatusLabels[entry.toStatus] ?? entry.toStatus}
                                                </span>
                                                {entry.note && (
                                                    <span className="text-muted-foreground"> — {entry.note}</span>
                                                )}
                                                <div className="text-xs text-muted-foreground">
                                                    {new Date(entry.createdAt).toLocaleString('id-ID')}
                                                </div>
                                            </li>
                                        ))}
                                    </ol>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="space-y-6 lg:sticky lg:top-24 lg:self-start">
                        <Card>
                            <CardHeader><CardTitle>Ringkasan</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between items-center gap-2">
                                    <span className="text-sm">Pesanan</span>
                                    <Badge>{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge>
                                </div>
                                <div className="flex justify-between items-center gap-2">
                                    <span className="text-sm">Pembayaran</span>
                                    <Badge variant="outline">
                                        {paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}
                                    </Badge>
                                </div>
                                <div className="flex justify-between gap-2 text-sm">
                                    <span className="text-muted-foreground">Metode</span>
                                    <span>{paymentMethodLabels[order.paymentMethod] ?? order.paymentMethod}</span>
                                </div>
                                {(order.courier || order.trackingNumber) && (
                                    <div className="text-sm space-y-1 pt-2 border-t">
                                        {order.courier && (
                                            <p>
                                                <span className="text-muted-foreground">Kurir:</span> {order.courier}
                                                {order.courierService && ` · ${order.courierService}`}
                                            </p>
                                        )}
                                        {order.trackingNumber && (
                                            <p><span className="text-muted-foreground">Resi:</span> {order.trackingNumber}</p>
                                        )}
                                    </div>
                                )}
                                {activeReturnRequest && (
                                    <div className="pt-2 border-t">
                                        <Button size="sm" variant="outline" className="w-full" asChild>
                                            <Link href={`/admin/returns/${activeReturnRequest.id}`}>
                                                Lihat Retur #{activeReturnRequest.requestNumber}
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Progress</CardTitle></CardHeader>
                            <CardContent>
                                <ol className="flex justify-between gap-1 text-[10px] sm:text-xs">
                                    {ORDER_FLOW_STEPS.map((label, index) => {
                                        const step = index + 1;
                                        const active = step === flowStep;
                                        const done = step < flowStep;
                                        return (
                                            <li
                                                key={label}
                                                className={`flex-1 text-center ${active ? 'font-semibold text-primary' : done ? 'text-muted-foreground' : 'text-muted-foreground/60'}`}
                                            >
                                                <div
                                                    className={`mx-auto mb-1 h-2 w-2 rounded-full ${active ? 'bg-primary' : done ? 'bg-primary/40' : 'bg-muted'}`}
                                                />
                                                {label}
                                            </li>
                                        );
                                    })}
                                </ol>
                            </CardContent>
                        </Card>

                        {(interactiveActions.length > 0 || infoActions.length > 0) && (
                            <Card>
                                <CardHeader><CardTitle>Langkah Selanjutnya</CardTitle></CardHeader>
                                <CardContent className="space-y-2">
                                    {infoActions.map((action, i) => (
                                        <p key={i} className="text-sm text-muted-foreground pb-1">
                                            {action.hint}
                                        </p>
                                    ))}
                                    {interactiveActions.map((action) => (
                                        <Button
                                            key={`${action.key}-${action.confirmationId ?? ''}`}
                                            variant={action.variant}
                                            className="w-full"
                                            onClick={() => handleAction(action)}
                                        >
                                            {action.label}
                                        </Button>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {paymentConfirmations.length > 0 && (
                            <Card>
                                <CardHeader><CardTitle>Konfirmasi dari Pembeli</CardTitle></CardHeader>
                                <CardContent className="space-y-3">
                                    {paymentConfirmations.map((pc) => (
                                        <div key={pc.id} className="border rounded-lg p-3 text-sm space-y-2">
                                            <div className="flex justify-between gap-2">
                                                <span className="font-medium">{pc.senderName}</span>
                                                <Badge variant={pc.status === 'pending' ? 'secondary' : 'outline'}>
                                                    {pc.status}
                                                </Badge>
                                            </div>
                                            <p>{formatRupiah(pc.amountClaimed)} · {pc.transferDate}</p>
                                            {pc.bank && (
                                                <p className="text-muted-foreground text-xs">
                                                    {pc.bank.bankName} · {pc.bank.accountNumber}
                                                </p>
                                            )}
                                            {pc.adminNote && (
                                                <p className="text-xs text-muted-foreground">Catatan: {pc.adminNote}</p>
                                            )}
                                            {pc.proofImageUrl && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => setProofUrl(pc.proofImageUrl!)}
                                                >
                                                    Lihat Bukti
                                                </Button>
                                            )}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </AdminContent>

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

            <Dialog open={rejectOpen} onOpenChange={(open) => { setRejectOpen(open); if (!open) rejectForm.reset(); }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Konfirmasi Pembayaran</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitReject} className="space-y-3">
                        <div>
                            <Label htmlFor="admin_note">Alasan penolakan</Label>
                            <Textarea
                                id="admin_note"
                                value={rejectForm.data.admin_note}
                                onChange={(e) => rejectForm.setData('admin_note', e.target.value)}
                                required
                                minLength={5}
                                rows={3}
                            />
                        </div>
                        <div className="flex gap-2 justify-end">
                            <Button type="button" variant="outline" onClick={() => setRejectOpen(false)}>Batal</Button>
                            <Button type="submit" variant="destructive" disabled={rejectForm.processing}>Tolak</Button>
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
