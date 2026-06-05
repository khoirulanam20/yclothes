import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Category = {
    id: number;
    name: string;
    slug: string;
    order: number;
    depth: number;
    parentName?: string | null;
    productsCount: number;
    childrenCount: number;
};

type Props = {
    categories: Category[];
};

export default function Index({ categories }: Props) {
    return (
        <AdminLayout title="Kategori" breadcrumbs={[{ label: 'Kategori' }]}>
            <Head title="Kategori" />
            <AdminPageHeader title="Kategori" createHref="/admin/categories/create" createLabel="Tambah Kategori" />

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Urutan</TableHead>
                                <TableHead>Produk</TableHead>
                                <TableHead>Sub</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.map((cat) => (
                                <TableRow key={cat.id}>
                                    <TableCell className="font-medium">
                                        <div style={{ paddingLeft: `${cat.depth * 1.25}rem` }}>
                                            {cat.depth > 0 && (
                                                <span className="text-muted-foreground mr-1">└</span>
                                            )}
                                            {cat.name}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <code className="text-xs">{cat.slug}</code>
                                    </TableCell>
                                    <TableCell>{cat.order}</TableCell>
                                    <TableCell>{cat.productsCount}</TableCell>
                                    <TableCell>
                                        {cat.childrenCount > 0 ? (
                                            <Badge variant="secondary">{cat.childrenCount}</Badge>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/categories/create?parent_id=${cat.id}`}>
                                                    Sub
                                                </Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/categories/${cat.id}/edit`}>Edit</Link>
                                            </Button>
                                            <DeleteRecordButton
                                                href={`/admin/categories/${cat.id}`}
                                                name={cat.name}
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
