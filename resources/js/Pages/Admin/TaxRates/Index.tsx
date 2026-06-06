import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CONFIGURATION_HREF, configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type TaxRate = { id: number; name: string; rate: number; type: string; isActive?: boolean; categoriesCount?: number };
type Props = { rates: TaxRate[] };

export default function Index({ rates }: Props) {
    return (
        <AdminLayout title="Tarif Pajak" breadcrumbs={configurationSectionBreadcrumbs('Tarif Pajak')}>
            <Head title="Tarif Pajak" />
            <AdminContent>
            <AdminPageHeader title="Tarif Pajak" backHref={CONFIGURATION_HREF} createHref="/admin/tax-rates/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Rate</TableHead><TableHead>Tipe</TableHead><TableHead>Kategori</TableHead><TableHead>Status</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{rates.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell>{r.rate}{r.type === 'percentage' ? '%' : ''}</TableCell>
                            <TableCell><Badge variant="outline">{r.type}</Badge></TableCell><TableCell>{r.categoriesCount ?? 0}</TableCell>
                            <TableCell>{r.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/tax-rates/${r.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/tax-rates/${r.id}`} name={r.name} />
                                </AdminTableActions>
                            </TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            </AdminContent>
        </AdminLayout>
    );
}
