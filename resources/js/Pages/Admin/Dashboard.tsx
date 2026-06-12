import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels } from '@/lib/order-status';

type Product = {
    id: number;
    name: string;
    slug: string;
    finalPrice: number;
    badge?: string | null;
    category?: { name: string } | null;
};

type Order = {
    id: number;
    orderNumber: string;
    customerName: string;
    grandTotal: number;
    orderStatus: string;
};

type LowStockItem = {
    product: { name: string };
    warehouse?: { name: string } | null;
    stock: number;
};

type Activity = {
    id: number;
    action: string;
    createdAt: string;
    user?: { name: string } | null;
};

type Props = {
    productCount: number;
    categoryCount: number;
    orderCount: number;
    pendingCount: number;
    latestProducts: Product[];
    latestOrders: Order[];
    lowStockItems: LowStockItem[];
    recentActivities: Activity[];
};

export default function Dashboard({
    productCount,
    categoryCount,
    orderCount,
    pendingCount,
    latestProducts,
    latestOrders,
    lowStockItems,
    recentActivities,
}: Props) {
    return (
        <AdminLayout title="Dasbor" breadcrumbs={[{ label: 'Dasbor' }]}>
            <Head title="Dasbor" />
            <AdminContent>
            <AdminPageHeader title="Dasbor" />

            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4" data-tour="dashboard-stats">
                <StatCard label="Total Pesanan" value={orderCount} />
                <StatCard label="Perlu Tindakan" value={pendingCount} />
                <StatCard label="Total Produk" value={productCount} />
                <StatCard label="Total Kategori" value={categoryCount} />
            </div>

            {lowStockItems.length > 0 && (
                <Card className="mb-6 border-warning">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-warning">Stok Menipis</CardTitle>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/inventories">Kelola Stok</Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead>Gudang</TableHead>
                                        <TableHead className="text-right">Stok</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {lowStockItems.slice(0, 10).map((item, i) => (
                                        <TableRow key={i}>
                                            <TableCell>{item.product.name}</TableCell>
                                            <TableCell>{item.warehouse?.name ?? '—'}</TableCell>
                                            <TableCell className="text-right font-bold text-destructive">
                                                {item.stock}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </AdminTableScroll>
                    </CardContent>
                </Card>
            )}

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Produk Terbaru</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <AdminTableScroll>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Harga</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {latestProducts.map((product) => (
                                    <TableRow key={product.id}>
                                        <TableCell>
                                            <Link
                                                href={`/admin/products/${product.id}/edit`}
                                                className="font-medium hover:underline"
                                            >
                                                {product.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{product.category?.name ?? '—'}</TableCell>
                                        <TableCell>{formatRupiah(product.finalPrice)}</TableCell>
                                        <TableCell>
                                            {product.badge ? (
                                                <Badge variant="secondary">{product.badge}</Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </AdminTableScroll>
                </CardContent>
            </Card>

            <Card className="mb-6" data-tour="dashboard-orders">
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>Pesanan Terbaru</CardTitle>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/orders">Lihat Semua</Link>
                    </Button>
                </CardHeader>
                <CardContent className="p-0">
                    <AdminTableScroll>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>No. Pesanan</TableHead>
                                    <TableHead>Pemesan</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {latestOrders.map((order) => (
                                    <TableRow key={order.id}>
                                        <TableCell>
                                            <Link
                                                href={`/admin/orders/${order.id}`}
                                                className="font-semibold hover:underline"
                                            >
                                                {order.orderNumber}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{order.customerName}</TableCell>
                                        <TableCell>{formatRupiah(order.grandTotal)}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {orderStatusLabels[order.orderStatus] ?? order.orderStatus}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </AdminTableScroll>
                </CardContent>
            </Card>

            {recentActivities.length > 0 && (
                <Card data-tour="dashboard-activity">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Aktivitas Terbaru</CardTitle>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/activity-logs">Lihat Semua</Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentActivities.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell className="text-muted-foreground text-sm">
                                                {log.createdAt
                                                    ? new Date(log.createdAt).toLocaleString('id-ID', {
                                                          day: 'numeric',
                                                          month: 'short',
                                                          hour: '2-digit',
                                                          minute: '2-digit',
                                                      })
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="text-sm">{log.user?.name ?? '—'}</TableCell>
                                            <TableCell>
                                                <code className="text-xs">{log.action.slice(0, 40)}</code>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </AdminTableScroll>
                    </CardContent>
                </Card>
            )}
            </AdminContent>
        </AdminLayout>
    );
}

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <Card>
            <CardContent className="p-4">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="text-3xl font-bold mt-1">{value}</p>
            </CardContent>
        </Card>
    );
}
