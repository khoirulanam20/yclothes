import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminDuplicateAction, AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';

type ProductRow = {
    id: number;
    name: string;
    sku?: string;
    type?: string;
    imageUrl?: string;
    finalPrice: number;
    badge?: string | null;
    isActive?: boolean;
    category?: { name: string } | null;
};

type Props = { products: Paginated<ProductRow> };

const typeLabel: Record<string, string> = {
    simple: 'Tunggal',
    configurable: 'Varian',
};

export default function Index({ products }: Props) {
    const duplicate = (id: number) => {
        router.post(`/admin/products/${id}/duplicate`);
    };

    return (
        <AdminLayout title="Produk" breadcrumbs={[{ label: 'Produk' }]}>
            <Head title="Produk" />
            <AdminContent>
                <AdminPageHeader title="Produk" createHref="/admin/products/create" />
                <Card>
                    <CardContent className="p-0">
                        <AdminTableScroll>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Gambar</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Tipe</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead>Harga</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.data.map((p) => (
                                        <TableRow key={p.id}>
                                            <TableCell>
                                                {p.imageUrl ? (
                                                    <img src={p.imageUrl} alt="" className="h-12 w-12 rounded object-cover" />
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell className="font-medium">{p.name}</TableCell>
                                            <TableCell className="text-muted-foreground">{p.sku ?? '—'}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{typeLabel[p.type ?? ''] ?? p.type ?? '—'}</Badge>
                                            </TableCell>
                                            <TableCell>{p.category?.name ?? '—'}</TableCell>
                                            <TableCell>{formatRupiah(p.finalPrice)}</TableCell>
                                            <TableCell>
                                                <Badge variant={p.isActive ? 'default' : 'secondary'}>
                                                    {p.isActive ? 'Aktif' : 'Draft'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <AdminTableActions>
                                                    <AdminEditAction href={`/admin/products/${p.id}/edit`} />
                                                    <AdminDuplicateAction onClick={() => duplicate(p.id)} />
                                                    <DeleteRecordButton href={`/admin/products/${p.id}`} name={p.name} />
                                                </AdminTableActions>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </AdminTableScroll>
                    </CardContent>
                </Card>
                <PaginationLinks pagination={products} />
            </AdminContent>
        </AdminLayout>
    );
}
