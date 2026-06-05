import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type CatalogRule = { id: number; name: string; ruleType: string; isActive?: boolean; startDate?: string; endDate?: string };
type Props = { rules: CatalogRule[] };

export default function Index({ rules }: Props) {
    return (
        <AdminLayout title="Aturan Katalog" breadcrumbs={[{ label: 'Aturan Katalog' }]}>
            <Head title="Aturan Katalog" />
            <AdminPageHeader title="Aturan Katalog" createHref="/admin/catalog-rules/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Tipe Rule</TableHead><TableHead>Status</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{rules.map((r) => (
                        <TableRow key={r.id}>
                            <TableCell>{r.name}</TableCell><TableCell><Badge variant="outline">{r.ruleType}</Badge></TableCell>
                            <TableCell>{r.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/catalog-rules/${r.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/catalog-rules/${r.id}`} name={r.name} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
