import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type FaqCategory = { id: number; name: string; sortOrder?: number; itemsCount?: number };

type Props = { categories: FaqCategory[] };

export default function Index({ categories }: Props) {
    return (
        <AdminLayout title="Kategori FAQ" breadcrumbs={[{ label: 'FAQ' }]}>
            <Head title="Kategori FAQ" />
            <AdminContent>
            <AdminPageHeader title="Kategori FAQ" createHref="/admin/faq-categories/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table>
                    <TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Urutan</TableHead><TableHead>Items</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>
                        {categories.map((cat) => (
                            <TableRow key={cat.id}>
                                <TableCell className="font-medium">{cat.name}</TableCell>
                                <TableCell>{cat.sortOrder ?? 0}</TableCell>
                                <TableCell><Link href={`/admin/faq-categories/${cat.id}/items`} className="text-primary hover:underline">{cat.itemsCount ?? 0} item</Link></TableCell>
                                <TableCell><div className="flex gap-1">
                                    <Button variant="outline" size="sm" asChild><Link href={`/admin/faq-categories/${cat.id}/items`}>Items</Link></Button>
                                    <Button variant="outline" size="sm" asChild><Link href={`/admin/faq-categories/${cat.id}/edit`}>Edit</Link></Button>
                                    <DeleteRecordButton href={`/admin/faq-categories/${cat.id}`} name={cat.name} />
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
