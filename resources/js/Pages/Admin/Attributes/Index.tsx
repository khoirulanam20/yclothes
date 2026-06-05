import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { DeleteRecordButton } from '@/components/admin/DeleteRecordButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Attribute = { id: number; name: string; code: string; type: string; isRequired?: boolean; isFilterable?: boolean };
type Props = { attributes: Attribute[] };

export default function Index({ attributes }: Props) {
    return (
        <AdminLayout title="Atribut" breadcrumbs={[{ label: 'Atribut' }]}>
            <Head title="Atribut" />
            <AdminPageHeader title="Atribut" createHref="/admin/attributes/create" />
            <Card><CardContent className="p-0">
                <Table><TableHeader><TableRow><TableHead>Nama</TableHead><TableHead>Code</TableHead><TableHead>Tipe</TableHead><TableHead>Flags</TableHead><TableHead>Aksi</TableHead></TableRow></TableHeader>
                    <TableBody>{attributes.map((a) => (
                        <TableRow key={a.id}>
                            <TableCell>{a.name}</TableCell><TableCell><code>{a.code}</code></TableCell>
                            <TableCell><Badge variant="outline">{a.type}</Badge></TableCell>
                            <TableCell className="text-xs">{a.isRequired && 'Required '}{a.isFilterable && 'Filterable'}</TableCell>
                            <TableCell><div className="flex gap-1">
                                <Button variant="outline" size="sm" asChild><Link href={`/admin/attributes/${a.id}/edit`}>Edit</Link></Button>
                                <DeleteRecordButton href={`/admin/attributes/${a.id}`} name={a.name} />
                            </div></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
            </CardContent></Card>
        </AdminLayout>
    );
}
