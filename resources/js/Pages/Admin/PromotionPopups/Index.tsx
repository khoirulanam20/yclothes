import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Popup = {
    id: number; title: string; imageUrl?: string | null; isActive: boolean;
    startDate?: string; endDate?: string; priority: number;
};

type Props = { popups: Popup[] };

export default function Index({ popups }: Props) {
    return (
        <AdminLayout title="Pop up Promosi" breadcrumbs={[{ label: 'Pop up Promosi' }]}>
            <Head title="Pop up Promosi" />
            <AdminPageHeader title="Pop up Promosi" createHref="/admin/promotion-popups/create" createLabel="Tambah Pop up" />
            <Card><CardContent className="p-0">
                <table className="w-full text-sm">
                    <thead><tr className="border-b text-left"><th className="p-3">Judul</th><th className="p-3">Periode</th><th className="p-3">Status</th><th className="p-3"></th></tr></thead>
                    <tbody>
                        {popups.map((p) => (
                            <tr key={p.id} className="border-b">
                                <td className="p-3">{p.title}</td>
                                <td className="p-3 text-muted-foreground">{p.startDate?.slice(0, 16)} — {p.endDate?.slice(0, 16)}</td>
                                <td className="p-3">{p.isActive ? 'Aktif' : 'Nonaktif'}</td>
                                <td className="p-3 text-right"><Button variant="outline" size="sm" asChild><Link href={`/admin/promotion-popups/${p.id}/edit`}>Edit</Link></Button></td>
                            </tr>
                        ))}
                        {popups.length === 0 && <tr><td colSpan={4} className="p-6 text-center text-muted-foreground">Belum ada pop up.</td></tr>}
                    </tbody>
                </table>
            </CardContent></Card>
        </AdminLayout>
    );
}
