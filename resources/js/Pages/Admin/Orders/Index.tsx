import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminTableActions, AdminViewAction } from '@/components/admin/AdminTableActions';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type Order = {
    id: number; orderNumber: string; customerName: string; grandTotal: number;
    orderStatus: string; paymentStatus: string; createdAt?: string; itemsCount?: number;
};

type Props = { orders: Paginated<Order>; awaitingActionCount?: number };

export default function Index({ orders, awaitingActionCount = 0 }: Props) {
    return (
        <AdminLayout title="Pesanan" breadcrumbs={[{ label: 'Pesanan' }]}>
            <Head title="Pesanan" />
            <AdminContent>
                <AdminPageHeader title="Pesanan" />
                {awaitingActionCount > 0 && (
                    <div className="mb-4 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm">
                        <span className="font-medium">{awaitingActionCount} pesanan</span>
                        {' '}membutuhkan tindakan admin (verifikasi pembayaran, proses, atau pengiriman).
                    </div>
                )}
                <Card>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader><TableRow>
                                    <TableHead>No. Pesanan</TableHead><TableHead>Pemesan</TableHead><TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead><TableHead>Bayar</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead>
                                </TableRow></TableHeader>
                                <TableBody>
                                    {orders.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="font-semibold">{order.orderNumber}</TableCell>
                                            <TableCell>{order.customerName}</TableCell>
                                            <TableCell>{formatRupiah(order.grandTotal)}</TableCell>
                                            <TableCell><Badge variant="secondary">{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge></TableCell>
                                            <TableCell>{paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}</TableCell>
                                            <TableCell className="text-right">
                                                <AdminTableActions>
                                                    <AdminViewAction href={`/admin/orders/${order.id}`} />
                                                </AdminTableActions>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </AdminTableScroll>
                    </CardContent>
                </Card>
                <PaginationLinks pagination={orders} />
            </AdminContent>
        </AdminLayout>
    );
}
