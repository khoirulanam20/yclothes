import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminTableScroll } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { PaginationLinks, type Paginated } from '@/components/admin/PaginationLinks';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Log = { id: number; action: string; createdAt?: string; user?: { name: string } | null };
type User = { id: number; name: string };
type Filters = { user_id?: string; action?: string; date_from?: string; date_to?: string };
type Props = { logs: Paginated<Log>; users: User[]; filters: Filters };

export default function Index({ logs, users, filters }: Props) {
    const { data, setData, get, processing } = useForm({
        user_id: filters.user_id ?? '',
        action: filters.action ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        get('/admin/activity-logs', { preserveState: true });
    };

    return (
        <AdminLayout title="Log Aktivitas" breadcrumbs={[{ label: 'Log Aktivitas' }]}>
            <Head title="Log Aktivitas" />
            <AdminContent>
            <AdminPageHeader title="Log Aktivitas" />
            <Card className="mb-4"><CardContent className="p-4">
                <form onSubmit={submit} className="grid md:grid-cols-4 gap-3 items-end">
                    <div><Label htmlFor="user_id">User</Label>
                        <select id="user_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.user_id} onChange={(e) => setData('user_id', e.target.value)}>
                            <option value="">Semua</option>
                            {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select></div>
                    <div><Label htmlFor="action">Action</Label><Input id="action" value={data.action} onChange={(e) => setData('action', e.target.value)} /></div>
                    <div><Label htmlFor="date_from">Dari</Label><Input id="date_from" type="date" value={data.date_from} onChange={(e) => setData('date_from', e.target.value)} /></div>
                    <div><Label htmlFor="date_to">Sampai</Label><Input id="date_to" type="date" value={data.date_to} onChange={(e) => setData('date_to', e.target.value)} /></div>
                    <Button type="submit" disabled={processing}>Filter</Button>
                </form>
            </CardContent></Card>
            <Card><CardContent className="p-0">
                <AdminTableScroll>
                        <Table><TableHeader><TableRow><TableHead>Waktu</TableHead><TableHead>User</TableHead><TableHead>Action</TableHead></TableRow></TableHeader>
                    <TableBody>{logs.data.map((log) => (
                        <TableRow key={log.id}>
                            <TableCell className="text-sm">{log.createdAt ? new Date(log.createdAt).toLocaleString('id-ID') : '—'}</TableCell>
                            <TableCell>{log.user?.name ?? '—'}</TableCell>
                            <TableCell><code className="text-xs">{log.action}</code></TableCell>
                        </TableRow>
                    ))}</TableBody></Table>
                    </AdminTableScroll>
            </CardContent></Card>
            <PaginationLinks pagination={logs} />
            </AdminContent>
        </AdminLayout>
    );
}
