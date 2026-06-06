import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { AdminEditAction, AdminTableActions } from '@/components/admin/AdminTableActions';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CONFIGURATION_HREF, configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string; isActive?: boolean };
type Props = { banks: Bank[] };

export default function Index({ banks }: Props) {
    return (
        <AdminLayout title="Rekening Bank" breadcrumbs={configurationSectionBreadcrumbs('Rekening Transfer')}>
            <Head title="Rekening Bank" />
            <AdminContent>
            <AdminPageHeader title="Rekening Bank" backHref={CONFIGURATION_HREF} createHref="/admin/payment-banks/create" />
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Bank</TableHead><TableHead>No. Rekening</TableHead><TableHead>Atas Nama</TableHead><TableHead>Status</TableHead><TableHead className="w-[1%] whitespace-nowrap text-right">Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{banks.map((b) => (
                        <TableRow key={b.id}>
                            <TableCell>{b.bankName}</TableCell><TableCell>{b.accountNumber}</TableCell><TableCell>{b.accountName}</TableCell>
                            <TableCell>{b.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell className="text-right">
                                <AdminTableActions>
                                    <AdminEditAction href={`/admin/payment-banks/${b.id}/edit`} />
                                    <DeleteRecordButton href={`/admin/payment-banks/${b.id}`} name={b.bankName} />
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
