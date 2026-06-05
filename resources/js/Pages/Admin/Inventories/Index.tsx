import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Inventory = {
    id: number; stock: number; lowStockThreshold?: number;
    product?: { name: string } | null; warehouse?: { name: string } | null;
};

type Props = { inventories: Paginated<Inventory> };

export default function Index({ inventories }: Props) {
    return (
        <AdminLayout title="Stok" breadcrumbs={[{ label: 'Stok' }]}>
            <Head title="Stok" />
            <AdminPageHeader title="Stok" createHref="/admin/inventories/create" />
            <Card><CardContent className="p-0">
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
        </AdminLayout>
    );
}
