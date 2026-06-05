import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string; isActive?: boolean };
type Props = { banks: Bank[] };

export default function Index({ banks }: Props) {
    return (
        <AdminLayout title="Rekening Bank" breadcrumbs={[{ label: 'Rekening' }]}>
            <Head title="Rekening Bank" />
            <AdminPageHeader title="Rekening Bank" createHref="/admin/payment-banks/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Bank</TableHead><TableHead>No. Rekening</TableHead><TableHead>Atas Nama</TableHead><TableHead>Status</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{banks.map((b) => (
                        <TableRow key={b.id}>
                            <TableCell>{b.bankName}</TableCell><TableCell>{b.accountNumber}</TableCell><TableCell>{b.accountName}</TableCell>
                            <TableCell>{b.isActive ? 'Aktif' : 'Nonaktif'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/payment-banks/${b.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/payment-banks/${b.id}`} name={b.bankName} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
