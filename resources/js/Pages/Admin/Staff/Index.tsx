import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Staff = { id: number; name: string; email: string; isAdmin?: boolean; role?: { name: string } | null };
type Props = { staff: Staff[] };

export default function Index({ staff }: Props) {
    return (
        <AdminLayout title="Staff" breadcrumbs={[{ label: 'Staff' }]}>
            <Head title="Staff" />
            <AdminPageHeader title="Staff" createHref="/admin/staff/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Email</TableHead><TableHead>Role</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{staff.map((s) => (
                        <TableRow key={s.id}>
                            <TableCell>{s.name}</TableCell><TableCell>{s.email}</TableCell>
                            <TableCell>{s.role ? <Badge variant="outline">{s.role.name}</Badge> : s.isAdmin ? 'Super Admin' : '—'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/staff/${s.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/staff/${s.id}`} name={s.name} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
