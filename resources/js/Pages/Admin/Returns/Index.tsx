import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { returnStatusLabels } from '@/lib/order-status';

type ReturnItem = { id: number; requestNumber: string; status: string; orderNumber?: string | null; createdAt: string };
type Props = { returns: ReturnItem[] };

export default function Index({ returns }: Props) {
    return (
        <AdminLayout title="Retur" breadcrumbs={[{ label: 'Retur' }]}>
            <Head title="Retur" />
            <AdminPageHeader
                title="Pengajuan Retur"
                actions={
                    <Button size="sm" variant="outline" asChild><Link href="/admin/returns/policy">Kebijakan Retur</Link></Button>
                }
            />
            <div className="space-y-3">
                {returns.length === 0 ? (
                    <Card><CardContent className="py-8 text-center text-muted-foreground">Belum ada pengajuan retur.</CardContent></Card>
                ) : returns.map((r) => (
                    <Card key={r.id}>
                        <CardContent className="flex justify-between items-center py-4">
                            <div>
                                <p className="font-semibold">{r.requestNumber}</p>
                                <p className="text-sm text-muted-foreground">#{r.orderNumber}</p>
                                <Badge className="mt-1">{returnStatusLabels[r.status] ?? r.status}</Badge>
                            </div>
                            <Button size="sm" asChild><Link href={`/admin/returns/${r.id}`}>Detail</Link></Button>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AdminLayout>
    );
}
