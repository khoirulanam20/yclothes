import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Slider = { id: number; title?: string | null; imageUrl: string; linkUrl?: string | null; sortOrder?: number; isActive?: boolean };

type Props = { sliders: Paginated<Slider> };

export default function Index({ sliders }: Props) {
    return (
        <AdminLayout title="Slider" breadcrumbs={[{ label: 'Slider' }]}>
            <Head title="Slider" />
            <AdminContent>
            <AdminPageHeader title="Slider" createHref="/admin/sliders/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table>
                    <TableHeader><TableRow>
                        <TableHead>Gambar</TableHead><TableHead>Judul</TableHead><TableHead>Link</TableHead><TableHead>Aksi</TableHead>
                    </TableRow></TableHeader>
                    <TableBody>
                        {sliders.data.map((s) => (
                            <TableRow key={s.id}>
                                <TableCell><img src={s.imageUrl} alt="" className="h-12 rounded" /></TableCell>
                                <TableCell>{s.title ?? '—'}</TableCell>
                                <TableCell className="max-w-[200px] truncate">{s.linkUrl ?? '—'}</TableCell>
                                <TableCell><div className="flex gap-1">
                                    <Button variant="outline" size="sm" asChild><Link href={`/admin/sliders/${s.id}/edit`}>Edit</Link></Button>
                                    <DeleteRecordButton href={`/admin/sliders/${s.id}`} name={s.title ?? 'Slider'} />
                                </div></TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                    </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={sliders} />
            </AdminContent>
        </AdminLayout>
    );
}
