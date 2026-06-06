import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Inventory = {
    id: number;
    stock: number;
    lowStockThreshold?: number;
    displayName?: string;
    displaySku?: string | null;
    product?: { name: string; sku?: string; type?: string } | null;
    variant?: { sku: string; name?: string; label?: string | null } | null;
    warehouse?: { name: string } | null;
};

type Movement = {
    id: number;
    type: string;
    quantity: number;
    reason?: string | null;
    createdAt?: string;
    orderNumber?: string | null;
    displayName?: string;
    product?: { name: string } | null;
    variant?: { sku: string; label?: string | null } | null;
    warehouse?: { name: string } | null;
};

type WarehouseOption = { id: number; name: string };

type Filters = {
    search?: string;
    warehouse_id?: string;
};

const typeLabels: Record<string, string> = {
    in: 'Masuk',
    out: 'Keluar',
    transfer: 'Transfer',
    adjustment: 'Penyesuaian',
};

type Props = {
    inventories: Paginated<Inventory>;
    recentMovements: Movement[];
    warehouses: WarehouseOption[];
    filters: Filters;
};

export default function Index({ inventories, recentMovements, warehouses, filters }: Props) {
    const { data, setData, get, processing } = useForm({
        search: filters.search ?? '',
        warehouse_id: filters.warehouse_id ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        get('/admin/inventories', { preserveState: true, preserveScroll: true });
    };

    const resetFilters = () => {
        router.get('/admin/inventories', {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Stok" breadcrumbs={[{ label: 'Stok' }]}>
            <Head title="Stok" />
            <AdminContent>
            <AdminPageHeader title="Stok" createHref="/admin/inventories/create" />

            <Card className="mb-4">
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid gap-3 md:grid-cols-[1fr_220px_auto_auto] items-end">
                        <div>
                            <Label htmlFor="search">Cari barang</Label>
                            <Input
                                id="search"
                                value={data.search}
                                onChange={(e) => setData('search', e.target.value)}
                                placeholder="Nama produk, SKU produk, atau SKU varian"
                            />
                        </div>
                        <div>
                            <Label htmlFor="warehouse_id">Gudang</Label>
                            <select
                                id="warehouse_id"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={data.warehouse_id}
                                onChange={(e) => setData('warehouse_id', e.target.value)}
                            >
                                <option value="">Semua gudang</option>
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
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

            <Card className="mb-6">
                <CardContent className="p-0">
                    {inventories.data.length === 0 ? (
                        <p className="p-4 text-sm text-muted-foreground">Tidak ada data stok.</p>
                    ) : (
                        <AdminTableScroll>
                            <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Barang</TableHead>
                                    <TableHead>SKU</TableHead>
                                    <TableHead>Gudang</TableHead>
                                    <TableHead>Stok</TableHead>
                                    <TableHead>Threshold</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {inventories.data.map((inv) => (
                                    <TableRow key={inv.id}>
                                        <TableCell>
                                            <div className="font-medium">{inv.displayName ?? inv.product?.name ?? '—'}</div>
                                            {inv.variant?.label && (
                                                <div className="text-xs text-muted-foreground">
                                                    Varian: {inv.variant.label}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">{inv.displaySku ?? '—'}</TableCell>
                                        <TableCell>{inv.warehouse?.name ?? '—'}</TableCell>
                                        <TableCell
                                            className={
                                                inv.stock <= (inv.lowStockThreshold ?? 5)
                                                    ? 'font-bold text-destructive'
                                                    : ''
                                            }
                                        >
                                            {inv.stock}
                                        </TableCell>
                                        <TableCell>{inv.lowStockThreshold ?? 5}</TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/admin/inventories/${inv.id}/edit`}>Edit</Link>
                                                </Button>
                                                <DeleteRecordButton
                                                    href={`/admin/inventories/${inv.id}`}
                                                    name={inv.displayName ?? inv.product?.name ?? 'Stok'}
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        </AdminTableScroll>
                    )}
                </CardContent>
            </Card>
            <PaginationLinks pagination={inventories} />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                    <CardTitle className="text-base">Log Pergerakan Stok</CardTitle>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/stock-movements">Lihat Semua</Link>
                    </Button>
                </CardHeader>
                <CardContent className="p-0">
                    {recentMovements.length === 0 ? (
                        <p className="p-4 text-sm text-muted-foreground">Belum ada pergerakan stok.</p>
                    ) : (
                        <AdminTableScroll>
                            <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Barang</TableHead>
                                    <TableHead>Gudang</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Alasan</TableHead>
                                    <TableHead>Referensi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentMovements.map((m) => (
                                    <TableRow key={m.id}>
                                        <TableCell className="whitespace-nowrap text-sm">
                                            {m.createdAt ? new Date(m.createdAt).toLocaleString('id-ID') : '—'}
                                        </TableCell>
                                        <TableCell>{typeLabels[m.type] ?? m.type}</TableCell>
                                        <TableCell>{m.displayName ?? m.product?.name ?? '—'}</TableCell>
                                        <TableCell>{m.warehouse?.name ?? '—'}</TableCell>
                                        <TableCell>{m.quantity}</TableCell>
                                        <TableCell className="max-w-xs truncate">{m.reason ?? '—'}</TableCell>
                                        <TableCell>{m.orderNumber ? `#${m.orderNumber}` : '—'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        </AdminTableScroll>
                    )}
                </CardContent>
            </Card>
            </AdminContent>
        </AdminLayout>
    );
}
