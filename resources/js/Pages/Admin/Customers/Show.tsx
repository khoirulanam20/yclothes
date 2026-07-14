import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels } from '@/lib/order-status';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string;
    avatarUrl?: string | null;
    emailVerified: boolean;
    isActive: boolean;
    ordersCount: number;
    createdAt?: string;
    lastLoginAt?: string | null;
};

type Address = {
    id: number;
    label: string;
    recipientName: string;
    phone: string;
    streetAddress: string;
    provinceName?: string | null;
    regencyName?: string | null;
    districtName?: string | null;
    villageName?: string | null;
    postalCode?: string | null;
    isDefault: boolean;
};

type Order = {
    id: number;
    orderNumber: string;
    customerName: string;
    grandTotal: number;
    orderStatus: string;
    createdAt?: string;
};

type Props = {
    customer: Customer;
    addresses: Address[];
    orders: Order[];
};

function formatDateTime(iso?: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatAddress(address: Address): string {
    const parts = [
        address.streetAddress,
        address.villageName,
        address.districtName,
        address.regencyName,
        address.provinceName,
        address.postalCode,
    ].filter(Boolean);

    return parts.join(', ') || '—';
}

export default function Show({ customer, addresses, orders }: Props) {
    return (
        <AdminLayout
            title={customer.name}
            breadcrumbs={[
                { label: 'Pelanggan', href: '/admin/customers' },
                { label: customer.name },
            ]}
        >
            <Head title={customer.name} />
            <AdminContent>
                <AdminPageHeader title={customer.name} />

                <div className="grid gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Profil</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">Nama</p>
                                <p className="font-medium">{customer.name}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Email</p>
                                <p className="font-medium">{customer.email}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Telepon</p>
                                <p className="font-medium">{customer.phone}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Status</p>
                                <Badge variant={customer.isActive ? 'secondary' : 'outline'}>
                                    {customer.isActive ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Email terverifikasi</p>
                                <p className="font-medium">{customer.emailVerified ? 'Ya' : 'Belum'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Total pesanan</p>
                                <p className="font-medium">{customer.ordersCount}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Terdaftar</p>
                                <p className="font-medium">{formatDateTime(customer.createdAt)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Login terakhir</p>
                                <p className="font-medium">{formatDateTime(customer.lastLoginAt)}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Alamat ({addresses.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {addresses.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Belum ada alamat tersimpan.</p>
                            ) : (
                                addresses.map((address) => (
                                    <div key={address.id} className="rounded-lg border p-4">
                                        <div className="mb-2 flex flex-wrap items-center gap-2">
                                            <span className="font-medium">{address.label}</span>
                                            {address.isDefault && <Badge variant="secondary">Utama</Badge>}
                                        </div>
                                        <p className="text-sm">{address.recipientName} · {address.phone}</p>
                                        <p className="text-sm text-muted-foreground">{formatAddress(address)}</p>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Riwayat Pesanan</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {orders.length === 0 ? (
                                <p className="p-6 text-sm text-muted-foreground">Belum ada pesanan.</p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>No. Pesanan</TableHead>
                                            <TableHead>Total</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Tanggal</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {orders.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell>
                                                    <Link
                                                        href={`/admin/orders/${order.id}`}
                                                        className="font-semibold text-primary hover:underline"
                                                    >
                                                        {order.orderNumber}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>{formatRupiah(order.grandTotal)}</TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {orderStatusLabels[order.orderStatus] ?? order.orderStatus}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{formatDateTime(order.createdAt)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AdminContent>
        </AdminLayout>
    );
}
