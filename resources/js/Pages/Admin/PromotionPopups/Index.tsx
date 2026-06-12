import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Popup = {
    id: number; title: string; imageUrl?: string | null; isActive: boolean;
    startDate?: string; endDate?: string; priority: number;
};

type Props = { popups: Paginated<Popup> };

export default function Index({ popups }: Props) {
    return (
        <AdminLayout title="Pop up Promosi" breadcrumbs={[{ label: 'Pop up Promosi' }]}>
            <Head title="Pop up Promosi" />
            <AdminContent>
            <AdminPageHeader title="Pop up Promosi" createHref="/admin/promotion-popups/create" createLabel="Tambah Pop up" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Judul</TableHead>
                                <TableHead>Periode</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {popups.data.map((p) => (
                                <TableRow key={p.id}>
                                    <TableCell>{p.title}</TableCell>
                                    <TableCell className="text-muted-foreground">{p.startDate?.slice(0, 16)} — {p.endDate?.slice(0, 16)}</TableCell>
                                    <TableCell>{p.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                                    <TableCell className="text-right">
                                        <AdminTableActions>
                                            <AdminEditAction href={`/admin/promotion-popups/${p.id}/edit`} />
                                        </AdminTableActions>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {popups.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="py-6 text-center text-muted-foreground">
                                        Belum ada pop up.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={popups} />
            </AdminContent>
        </AdminLayout>
    );
}
