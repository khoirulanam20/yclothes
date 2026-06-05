import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type TaxRate = { id: number; name: string; rate: number; type: string; isActive?: boolean; categoriesCount?: number };
type Props = { rates: TaxRate[] };

export default function Index({ rates }: Props) {
    return (
        <AdminLayout title="Tarif Pajak" breadcrumbs={[{ label: 'Tarif Pajak' }]}>
            <Head title="Tarif Pajak" />
            <AdminPageHeader title="Tarif Pajak" createHref="/admin/tax-rates/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Rate</TableHead><TableHead>Tipe</TableHead><TableHead>Kategori</TableHead><TableHead>Status</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{rates.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell>{r.rate}{r.type === 'percentage' ? '%' : ''}</TableCell>
                            <TableCell><Badge variant="outline">{r.type}</Badge></TableCell><TableCell>{r.categoriesCount ?? 0}</TableCell>
                            <TableCell>{r.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/tax-rates/${r.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/tax-rates/${r.id}`} name={r.name} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
