import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type Order = {
    id: number; orderNumber: string; customerName: string; grandTotal: number;
    orderStatus: string; paymentStatus: string; createdAt?: string; itemsCount?: number;
};

type Props = { orders: Paginated<Order> };

export default function Index({ orders }: Props) {
    return (
        <AdminLayout title="Pesanan" breadcrumbs={[{ label: 'Pesanan' }]}>
            <Head title="Pesanan" />
            <AdminContent>
                <AdminPageHeader title="Pesanan" />
                <Card>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader><TableRow>
                                    <TableHead>No. Pesanan</TableHead><TableHead>Pemesan</TableHead><TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead><TableHead>Bayar</TableHead><TableHead>Aksi</TableHead>
                                </TableRow></TableHeader>
                                <TableBody>
                                    {orders.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="font-semibold">{order.orderNumber}</TableCell>
                                            <TableCell>{order.customerName}</TableCell>
                                            <TableCell>{formatRupiah(order.grandTotal)}</TableCell>
                                            <TableCell><Badge variant="secondary">{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge></TableCell>
                                            <TableCell>{paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}</TableCell>
                                            <TableCell><Button variant="outline" size="sm" asChild><Link href={`/admin/orders/${order.id}`}>Detail</Link></Button></TableCell>
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
