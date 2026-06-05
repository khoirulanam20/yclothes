import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Warehouse = { id: number; name: string; city?: string | null; isActive?: boolean };
type Props = { warehouses: Warehouse[] };

export default function Index({ warehouses }: Props) {
    return (
        <AdminLayout title="Gudang" breadcrumbs={[{ label: 'Gudang' }]}>
            <Head title="Gudang" />
            <AdminPageHeader title="Gudang" createHref="/admin/warehouses/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Kota</TableHead><TableHead>Status</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{warehouses.map((w) => (
                        <TableRow key={w.id}>
                            <TableCell>{w.name}</TableCell><TableCell>{w.city ?? '—'}</TableCell><TableCell>{w.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/warehouses/${w.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/warehouses/${w.id}`} name={w.name} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
