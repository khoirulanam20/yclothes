import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Inventory = {
    id: number; stock: number; lowStockThreshold?: number;
    product?: { name: string } | null; warehouse?: { name: string } | null;
};

type Movement = {
    id: number; type: string; quantity: number; reason?: string | null; createdAt?: string;
    orderNumber?: string | null;
    product?: { name: string } | null; warehouse?: { name: string } | null;
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
};

export default function Index({ inventories, recentMovements }: Props) {
    return (
        <AdminLayout title="Stok" breadcrumbs={[{ label: 'Stok' }]}>
            <Head title="Stok" />
            <AdminPageHeader title="Stok" createHref="/admin/inventories/create" />
            <Card className="mb-6"><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Produk</TableHead><TableHead>Gudang</TableHead><TableHead>Stok</TableHead><TableHead>Threshold</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{inventories.data.map((inv) => (
                        <TableRow key={inv.id}>
                            <TableCell>{inv.product?.name ?? '—'}</TableCell><TableCell>{inv.warehouse?.name ?? '—'}</TableCell>
                            <TableCell className={inv.stock <= (inv.lowStockThreshold ?? 5) ? 'text-destructive font-bold' : ''}>{inv.stock}</TableCell>
                            <TableCell>{inv.lowStockThreshold ?? 5}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/inventories/${inv.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/inventories/${inv.id}`} name={inv.product?.name ?? 'Stok'} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
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
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Produk</TableHead>
                                    <TableHead>Gudang</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Alasan</TableHead>
                                    <TableHead>Referensi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentMovements.map((m) => (
                                    <TableRow key={m.id}>
                                        <TableCell className="text-sm whitespace-nowrap">
                                            {m.createdAt ? new Date(m.createdAt).toLocaleString('id-ID') : '—'}
                                        </TableCell>
                                        <TableCell>{typeLabels[m.type] ?? m.type}</TableCell>
                                        <TableCell>{m.product?.name ?? '—'}</TableCell>
                                        <TableCell>{m.warehouse?.name ?? '—'}</TableCell>
                                        <TableCell>{m.quantity}</TableCell>
                                        <TableCell className="max-w-xs truncate">{m.reason ?? '—'}</TableCell>
                                        <TableCell>{m.orderNumber ? `#${m.orderNumber}` : '—'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
