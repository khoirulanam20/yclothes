import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Warehouse = { id: number; name: string; city?: string | null; isActive?: boolean };
type Props = { warehouses: Warehouse[] };

export default function Index({ warehouses }: Props) {
    return (
        <AdminLayout title="Gudang" breadcrumbs={[{ label: 'Gudang' }]}>
            <Head title="Gudang" />
            <AdminContent>
            <AdminPageHeader title="Gudang" createHref="/admin/warehouses/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Kota</TableHead><TableHead>Status</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{warehouses.map((w) => (
                        <TableRow key={w.id}>
                            <TableCell>{w.name}</TableCell><TableCell>{w.city ?? '—'}</TableCell><TableCell>{w.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/warehouses/${w.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/warehouses/${w.id}`} name={w.name} />
                                </AdminTableActions>
                            </TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            </AdminContent>
        </AdminLayout>
    );
}
