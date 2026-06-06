import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type NavItem = { id: number; menu: string; label: string; url: string; sortOrder?: number; isActive?: boolean; parentId?: number | null };

type Props = { items: NavItem[] };

export default function Index({ items }: Props) {
    return (
        <AdminLayout title="Navigasi" breadcrumbs={[{ label: 'Navigasi' }]}>
            <Head title="Navigasi" />
            <AdminContent>
            <AdminPageHeader title="Navigasi" createHref="/admin/navigation/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table>
                    <TableHeader><TableRow>
                        <TableHead>Menu</TableHead><TableHead>Label</TableHead><TableHead>URL</TableHead><TableHead>Urutan</TableHead><TableHead>Status</TableHead><TableHead>Aksi</TableHead>
                    </TableRow></TableHeader>
                    <TableBody>
                        {items.map((item) => (
                            <TableRow key={item.id}>
                                <TableCell><Badge variant="outline">{item.menu}</Badge></TableCell>
                                <TableCell>{item.label}</TableCell>
                                <TableCell className="max-w-[200px] truncate">{item.url}</TableCell>
                                <TableCell>{item.sortOrder ?? 0}</TableCell>
                                <TableCell>{item.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                                <TableCell><div className="flex gap-1">
                                    <Button variant="outline" size="sm" asChild><Link href={`/admin/navigation/${item.id}/edit`}>Edit</Link></Button>
                                    <DeleteRecordButton href={`/admin/navigation/${item.id}`} name={item.label} />
                                </div></TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                    </AdminTableScroll>
            </CardContent></Card>
            </AdminContent>
        </AdminLayout>
    );
}
