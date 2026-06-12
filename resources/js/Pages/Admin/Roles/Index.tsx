import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Role = { id: number; name: string; description?: string | null; permissions?: string[] };
type Props = { roles: Paginated<Role> };

export default function Index({ roles }: Props) {
    return (
        <AdminLayout title="Peran" breadcrumbs={[{ label: 'Peran' }]}>
            <Head title="Peran" />
            <AdminContent>
            <AdminPageHeader title="Peran" createHref="/admin/roles/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Deskripsi</TableHead><TableHead>Permissions</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{roles.data.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell className="max-w-xs truncate">{r.description ?? '—'}</TableCell>
                            <TableCell>{r.permissions?.length ?? 0}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/roles/${r.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/roles/${r.id}`} name={r.name} />
                                </AdminTableActions>
                            </TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={roles} />
            </AdminContent>
        </AdminLayout>
    );
}
