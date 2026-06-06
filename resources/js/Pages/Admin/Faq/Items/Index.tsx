import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type FaqItem = { id: number; question: string; sortOrder?: number; isActive?: boolean };
type FaqCategory = { id: number; name: string };
type Props = { category: FaqCategory; items: FaqItem[] };

export default function Index({ category, items }: Props) {
    return (
        <AdminLayout title={`FAQ — ${category.name}`} breadcrumbs={[{ label: 'FAQ', href: '/admin/faq-categories' }, { label: category.name }]}>
            <Head title={`FAQ — ${category.name}`} />
            <AdminContent>
            <AdminPageHeader title={`FAQ: ${category.name}`} createHref={`/admin/faq-categories/${category.id}/items/create`} />
            <Button variant="outline" size="sm" className="mb-4" asChild><Link href="/admin/faq-categories">← Kategori FAQ</Link></Button>
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table>
                    <TableHeader><TableRow><TableHead>Pertanyaan</TableHead><TableHead>Urutan</TableHead><TableHead>Status</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>
                        {items.map((item) => (
                            <TableRow key={item.id}>
                                <TableCell>{item.question}</TableCell>
                                <TableCell>{item.sortOrder ?? 0}</TableCell>
                                <TableCell>{item.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                                <TableCell className="text-right">
                                    <AdminTableActions>
                                        <AdminEditAction href={`/admin/faq-categories/${category.id}/items/${item.id}/edit`} />
                                        <DeleteRecordButton href={`/admin/faq-categories/${category.id}/items/${item.id}`} name={item.question} />
                                    </AdminTableActions>
                                </TableCell>
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
