import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

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

type Props = { movements: Paginated<Movement> };

export default function Index({ movements }: Props) {
    return (
        <AdminLayout title="Pergerakan Stok" breadcrumbs={[{ label: 'Pergerakan Stok' }]}>
            <Head title="Pergerakan Stok" />
            <AdminContent>
            <AdminPageHeader
                title="Pergerakan Stok"
                actions={
                    <>
                        <Button asChild><Link href="/admin/stock-movements/adjustment">Penyesuaian</Link></Button>
                        <Button variant="outline" asChild><Link href="/admin/stock-movements/transfer">Transfer</Link></Button>
                    </>
                }
            />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Waktu</TableHead><TableHead>Tipe</TableHead><TableHead>Produk</TableHead><TableHead>Gudang</TableHead><TableHead>Qty</TableHead><TableHead>Alasan</TableHead><TableHead>Referensi</TableHead></TableRow></TableHeader>
                    <TableBody>{movements.data.map((m) => (
                        <TableRow key={m.id}>
                            <TableCell className="text-sm">{m.createdAt ? new Date(m.createdAt).toLocaleString('id-ID') : '—'}</TableCell>
                            <TableCell>{typeLabels[m.type] ?? m.type}</TableCell>
                            <TableCell>{m.product?.name ?? '—'}</TableCell>
                            <TableCell>{m.warehouse?.name ?? '—'}</TableCell>
                            <TableCell>{m.quantity}</TableCell>
                            <TableCell className="max-w-xs truncate">{m.reason ?? '—'}</TableCell>
                            <TableCell>{m.orderNumber ? `#${m.orderNumber}` : '—'}</TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={movements} />
            </AdminContent>
        </AdminLayout>
    );
}
