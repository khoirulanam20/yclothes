import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminTableActions, AdminViewAction } from '@/components/admin/AdminTableActions';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string;
    isActive: boolean;
    ordersCount: number;
    createdAt?: string;
    lastLoginAt?: string | null;
};

type Filters = {
    search?: string;
    status?: string;
};

type Props = {
    customers: Paginated<Customer>;
    filters: Filters;
};

function formatDate(iso?: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function Index({ customers, filters }: Props) {
    const { data, setData, get, processing } = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        get('/admin/customers', { preserveState: true, preserveScroll: true });
    };

    const resetFilters = () => {
        router.get('/admin/customers', {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Pelanggan" breadcrumbs={[{ label: 'Pelanggan' }]}>
            <Head title="Pelanggan" />
            <AdminContent>
                <AdminPageHeader title="Pelanggan" />

                <Card className="mb-4">
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-[1fr_180px_auto_auto] items-end">
                            <div>
                                <Label htmlFor="search">Cari pelanggan</Label>
                                <Input
                                    id="search"
                                    value={data.search}
                                    onChange={(e) => setData('search', e.target.value)}
                                    placeholder="Nama, email, atau telepon"
                                />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                >
                                    <option value="">Semua</option>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                            <Button type="submit" disabled={processing}>
                                Filter
                            </Button>
                            <Button type="button" variant="outline" onClick={resetFilters} disabled={processing}>
                                Reset
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Telepon</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Pesanan</TableHead>
                                        <TableHead>Terdaftar</TableHead>
                                        <TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                                                Tidak ada pelanggan yang cocok dengan filter.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        customers.data.map((customer) => (
                                            <TableRow key={customer.id}>
                                                <TableCell className="font-medium">{customer.name}</TableCell>
                                                <TableCell>{customer.email}</TableCell>
                                                <TableCell>{customer.phone}</TableCell>
                                                <TableCell>
                                                    <Badge variant={customer.isActive ? 'secondary' : 'outline'}>
                                                        {customer.isActive ? 'Aktif' : 'Nonaktif'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{customer.ordersCount}</TableCell>
                                                <TableCell>{formatDate(customer.createdAt)}</TableCell>
                                                <TableCell className="text-right">
                                                    <AdminTableActions>
                                                        <AdminViewAction href={`/admin/customers/${customer.id}`} />
                                                    </AdminTableActions>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </AdminTableScroll>
                    </CardContent>
                </Card>
                <PaginationLinks pagination={customers} />
            </AdminContent>
        </AdminLayout>
    );
}
