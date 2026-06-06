import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminHelpPanel } from '@/components/admin/AdminHelpPanel';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { attributeFamilyHelp } from '@/lib/admin-help-content';

type Family = { id: number; name: string; attributesCount?: number; productsCount?: number };
type Props = { families: Family[] };

export default function Index({ families }: Props) {
    return (
        <AdminLayout title="Keluarga Atribut" breadcrumbs={[{ label: 'Atribut' }, { label: 'Keluarga Atribut' }]}>
            <Head title="Keluarga Atribut" />
            <AdminContent>
            <AdminPageHeader title="Keluarga Atribut" createHref="/admin/attribute-families/create" />
            <div className="mb-4">
                <AdminHelpPanel section={attributeFamilyHelp} defaultOpen />
            </div>
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Atribut</TableHead><TableHead>Produk</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{families.map((f) => (
                        <TableRow key={f.id}>
                            <TableCell>{f.name}</TableCell><TableCell>{f.attributesCount ?? 0}</TableCell><TableCell>{f.productsCount ?? 0}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/attribute-families/${f.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/attribute-families/${f.id}`} name={f.name} />
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
