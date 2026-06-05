import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type OrderItem = { id: number; productName: string; qty: number; unitPrice: number; subtotal: number; size?: string | null; color?: string | null };
type Order = {
    id: number; orderNumber: string; customerName: string; customerPhone: string; customerEmail: string;
    shippingAddress: string; shippingCity: string; shippingCost: number; totalPrice: number;
    taxAmount: number; discountAmount: number; grandTotal: number; paymentMethod: string;
    paymentStatus: string; orderStatus: string; courier?: string | null; courierService?: string | null;
    trackingNumber?: string | null; notes?: string | null; items: OrderItem[];
};

type Props = { order: Order };

export default function Show({ order }: Props) {
    const statusForm = useForm({
        order_status: order.orderStatus,
        courier: order.courier ?? '',
        courier_service: order.courierService ?? '',
        tracking_number: order.trackingNumber ?? '',
        notes: order.notes ?? '',
    });

    const submitStatus = (e: FormEvent) => {
        e.preventDefault();
        statusForm.post(`/admin/orders/${order.id}/status`, { preserveScroll: true });
    };

    const confirmPayment = () => {
        statusForm.post(`/admin/orders/${order.id}/payment`, { preserveScroll: true });
    };

    return (
        <AdminLayout
            title={`Pesanan #${order.orderNumber}`}
            breadcrumbs={[
                { label: 'Pesanan', href: '/admin/orders' },
                { label: `#${order.orderNumber}` },
            ]}
        >
            <Head title={`Pesanan #${order.orderNumber}`} />
            <AdminPageHeader
                title={`Pesanan #${order.orderNumber}`}
                backHref="/admin/orders"
            />

            <div className="grid lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card><CardHeader><CardTitle>Data Pemesan</CardTitle></CardHeader><CardContent className="grid sm:grid-cols-2 gap-2 text-sm">
                        <div><span className="text-muted-foreground">Nama:</span> {order.customerName}</div>
                        <div><span className="text-muted-foreground">WA:</span> {order.customerPhone}</div>
                        <div><span className="text-muted-foreground">Email:</span> {order.customerEmail}</div>
                        <div className="sm:col-span-2"><span className="text-muted-foreground">Alamat:</span> {order.shippingAddress}, {order.shippingCity}</div>
                    </CardContent></Card>

                    <Card><CardHeader><CardTitle>Produk</CardTitle></CardHeader><CardContent className="p-0">
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
                            <div className="flex justify-between font-bold text-lg pt-2"><span>Grand Total</span><span className="text-primary">{formatRupiah(order.grandTotal)}</span></div>
                        </div>
                    </CardContent></Card>
                </div>

                <div className="space-y-6">
                    <Card><CardHeader><CardTitle>Status</CardTitle></CardHeader><CardContent className="space-y-2">
                        <div className="flex justify-between"><span>Pesanan</span><Badge>{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge></div>
                        <div className="flex justify-between"><span>Pembayaran</span><Badge variant="outline">{paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}</Badge></div>
                        <div className="flex justify-between text-sm"><span>Metode</span><span>{order.paymentMethod}</span></div>
                        {order.paymentStatus !== 'paid' && (
                            <Button className="w-full mt-2" onClick={confirmPayment}>Konfirmasi Pembayaran</Button>
                        )}
                    </CardContent></Card>

                    <Card><CardHeader><CardTitle>Update Status</CardTitle></CardHeader><CardContent>
                        <form onSubmit={submitStatus} className="space-y-3">
                            <div><Label htmlFor="order_status">Status Pesanan</Label>
                                <select id="order_status" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={statusForm.data.order_status} onChange={(e) => statusForm.setData('order_status', e.target.value)}>
                                    {Object.entries(orderStatusLabels).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                                </select></div>
                            <div><Label htmlFor="courier">Kurir</Label><Input id="courier" value={statusForm.data.courier} onChange={(e) => statusForm.setData('courier', e.target.value)} /></div>
                            <div><Label htmlFor="courier_service">Layanan Kurir</Label><Input id="courier_service" value={statusForm.data.courier_service} onChange={(e) => statusForm.setData('courier_service', e.target.value)} /></div>
                            <div><Label htmlFor="tracking_number">No. Resi</Label><Input id="tracking_number" value={statusForm.data.tracking_number} onChange={(e) => statusForm.setData('tracking_number', e.target.value)} /></div>
                            <div><Label htmlFor="notes">Catatan</Label><Textarea id="notes" rows={3} value={statusForm.data.notes} onChange={(e) => statusForm.setData('notes', e.target.value)} /><FieldError message={statusForm.errors.notes} /></div>
                            <Button type="submit" disabled={statusForm.processing} className="w-full">Simpan Status</Button>
                        </form>
                    </CardContent></Card>
                </div>
            </div>
        </AdminLayout>
    );
}
