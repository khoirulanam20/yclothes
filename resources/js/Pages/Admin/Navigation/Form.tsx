import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type NavItem = { id: number; menu: string; label: string; url: string; sortOrder?: number; isActive?: boolean; parentId?: number | null };
type ParentOption = { id: number; label: string };
type Props = { item?: NavItem; parents?: ParentOption[] };

export default function Form({ item, parents = [] }: Props) {
    const isEdit = !!item?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        menu: item?.menu ?? 'header',
        parent_id: item?.parentId ?? '',
        label: item?.label ?? '',
        url: item?.url ?? '',
        sort_order: item?.sortOrder ?? 0,
        is_active: item?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/navigation/${item!.id}`, options);
        } else {
            post('/admin/navigation', options);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Navigasi' : 'Tambah Navigasi'}
            breadcrumbs={[
                { label: 'Navigasi', href: '/admin/navigation' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Navigasi' : 'Tambah Navigasi'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Navigasi' : 'Tambah Navigasi'}
                backHref="/admin/navigation"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="menu">Menu</Label>
                        <select id="menu" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.menu} onChange={(e) => setData('menu', e.target.value)}>
                            <option value="header">Header</option><option value="footer">Footer</option>
                        </select></div>
                    <div><Label htmlFor="parent_id">Parent (opsional)</Label>
                        <select id="parent_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.parent_id} onChange={(e) => setData('parent_id', e.target.value)}>
                            <option value="">— Tidak ada —</option>
                            {parents.map((p) => <option key={p.id} value={p.id}>{p.label}</option>)}
                        </select></div>
                    <div><Label htmlFor="label">Label</Label><Input id="label" value={data.label} onChange={(e) => setData('label', e.target.value)} required /><FieldError message={errors.label} /></div>
                    <div><Label htmlFor="url">URL</Label><Input id="url" value={data.url} onChange={(e) => setData('url', e.target.value)} required /><FieldError message={errors.url} /></div>
                    <div><Label htmlFor="sort_order">Urutan</Label><Input id="sort_order" type="number" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} /></div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/navigation">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
