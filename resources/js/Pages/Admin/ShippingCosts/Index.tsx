import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { CONFIGURATION_HREF, configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type ShippingCost = { id: number; cityName: string; cost: number; costPerKg?: number | null; isActive?: boolean };
type Props = { costs: ShippingCost[] };

export default function Index({ costs }: Props) {
    return (
        <AdminLayout title="Ongkos Kirim" breadcrumbs={configurationSectionBreadcrumbs('Ongkir')}>
            <Head title="Ongkos Kirim" />
            <AdminContent>
            <AdminPageHeader title="Ongkos Kirim" backHref={CONFIGURATION_HREF} createHref="/admin/shipping-costs/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Kota</TableHead><TableHead>Ongkir</TableHead><TableHead>Per Kg</TableHead><TableHead>Status</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{costs.map((c) => (
                        <TableRow key={c.id}>
                            <TableCell>{c.cityName}</TableCell><TableCell>{formatRupiah(c.cost)}</TableCell>
                            <TableCell>{c.costPerKg ? formatRupiah(c.costPerKg) : '—'}</TableCell>
                            <TableCell>{c.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/shipping-costs/${c.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/shipping-costs/${c.id}`} name={c.cityName} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            </AdminContent>
        </AdminLayout>
    );
}
