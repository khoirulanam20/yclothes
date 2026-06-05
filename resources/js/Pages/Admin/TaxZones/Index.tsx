import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type TaxZone = { id: number; province?: string | null; city?: string | null; taxRate?: { name: string } | null };
type Props = { zones: TaxZone[] };

export default function Index({ zones }: Props) {
    return (
        <AdminLayout title="Zona Pajak" breadcrumbs={[{ label: 'Zona Pajak' }]}>
            <Head title="Zona Pajak" />
            <AdminPageHeader title="Zona Pajak" createHref="/admin/tax-zones/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Provinsi</TableHead><TableHead>Kota</TableHead><TableHead>Tarif</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{zones.map((z) => (
                        <TableRow key={z.id}>
                            <TableCell>{z.province ?? '—'}</TableCell><TableCell>{z.city ?? '—'}</TableCell><TableCell>{z.taxRate?.name ?? '—'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/tax-zones/${z.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/tax-zones/${z.id}`} name={`${z.province} ${z.city}`} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
