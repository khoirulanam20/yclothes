import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Role = { id: number; name: string; description?: string | null; permissions?: string[] };
type Props = { roles: Role[] };

export default function Index({ roles }: Props) {
    return (
        <AdminLayout title="Peran" breadcrumbs={[{ label: 'Peran' }]}>
            <Head title="Peran" />
            <AdminPageHeader title="Peran" createHref="/admin/roles/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Deskripsi</TableHead><TableHead>Permissions</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{roles.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell className="max-w-xs truncate">{r.description ?? '—'}</TableCell>
                            <TableCell>{r.permissions?.length ?? 0}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/roles/${r.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/roles/${r.id}`} name={r.name} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
