import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import type { ProductCardData } from '@/components/ProductCard';

type Props = { products: Paginated<ProductCardData & { category?: { name: string } | null }> };

export default function Index({ products }: Props) {
    return (
        <AdminLayout title="Produk" breadcrumbs={[{ label: 'Produk' }]}>
            <Head title="Produk" />
            <AdminPageHeader title="Produk" createHref="/admin/products/create" />
            <Card><CardContent className="p-0">
                <Table>
                    <TableHeader><TableRow><TableHead>Gambar</TableHead><TableHead>Nama</TableHead><TableHead>Kategori</TableHead><TableHead>Harga</TableHead><TableHead>Badge</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>
                        {products.data.map((p) => (
                            <TableRow key={p.id}>
                                <TableCell><img src={p.imageUrl} alt="" className="h-12 w-12 object-cover rounded" /></TableCell>
                                <TableCell className="font-medium">{p.name}</TableCell>
                                <TableCell>{p.category?.name ?? '—'}</TableCell>
                                <TableCell>{formatRupiah(p.finalPrice)}</TableCell>
                                <TableCell>{p.badge ? <Badge>{p.badge}</Badge> : '—'}</TableCell>
                                <TableCell><div className="flex gap-1">
                                    <Button variant="outline" size="sm" asChild><Link href={`/admin/products/${p.id}/edit`}>Edit</Link></Button>
                                    <DeleteRecordButton href={`/admin/products/${p.id}`} name={p.name} />
                                </div></TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent></Card>
            <PaginationLinks pagination={products} />
        </AdminLayout>
    );
}
