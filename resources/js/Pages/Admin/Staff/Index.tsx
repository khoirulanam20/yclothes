import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Staff = { id: number; name: string; email: string; isAdmin?: boolean; role?: { name: string } | null };
type Props = { staff: Staff[] };

export default function Index({ staff }: Props) {
    return (
        <AdminLayout title="Staff" breadcrumbs={[{ label: 'Staff' }]}>
            <Head title="Staff" />
            <AdminContent>
            <AdminPageHeader title="Staff" createHref="/admin/staff/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Email</TableHead><TableHead>Role</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{staff.map((s) => (
                        <TableRow key={s.id}>
                            <TableCell>{s.name}</TableCell><TableCell>{s.email}</TableCell>
                            <TableCell>{s.role ? <Badge variant="outline">{s.role.name}</Badge> : s.isAdmin ? 'Super Admin' : '—'}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/staff/${s.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/staff/${s.id}`} name={s.name} />
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
