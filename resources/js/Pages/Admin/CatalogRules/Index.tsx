import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type CatalogRule = { id: number; name: string; ruleType: string; isActive?: boolean; startDate?: string; endDate?: string };
type Props = { rules: CatalogRule[] };

export default function Index({ rules }: Props) {
    return (
        <AdminLayout title="Aturan Katalog" breadcrumbs={[{ label: 'Aturan Katalog' }]}>
            <Head title="Aturan Katalog" />
            <AdminContent>
            <AdminPageHeader title="Aturan Katalog" createHref="/admin/catalog-rules/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Tipe Rule</TableHead><TableHead>Status</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{rules.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell><Badge variant="outline">{r.ruleType}</Badge></TableCell>
                            <TableCell>{r.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/catalog-rules/${r.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/catalog-rules/${r.id}`} name={r.name} />
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
