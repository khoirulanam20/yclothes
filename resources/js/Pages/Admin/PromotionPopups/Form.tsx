import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type Popup = {
    id: number; title: string; imageUrl?: string | null; buttonLabel?: string | null; buttonUrl?: string | null;
    displayDurationSeconds?: number; startDate?: string; endDate?: string;
    showOnPages?: string[]; isActive?: boolean; priority?: number;
};

type Props = { popup?: Popup; pageOptions: Record<string, string> };

export default function Form({ popup, pageOptions }: Props) {
    const isEdit = !!popup?.id;
    const { data, setData, post, transform, processing } = useForm({
        title: popup?.title ?? '',
        image: null as File | null,
        remove_image: false,
        button_label: popup?.buttonLabel ?? '',
        button_url: popup?.buttonUrl ?? '',
        display_duration_seconds: popup?.displayDurationSeconds ?? 0,
        start_date: popup?.startDate ?? '',
        end_date: popup?.endDate ?? '',
        show_on_pages: popup?.showOnPages ?? [] as string[],
        is_active: popup?.isActive ?? true,
        priority: popup?.priority ?? 0,
    });

    const togglePage = (key: string) => {
        setData('show_on_pages', data.show_on_pages.includes(key)
            ? data.show_on_pages.filter((p) => p !== key)
            : [...data.show_on_pages, key]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { forceFormData: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/promotion-popups/${popup!.id}`, opts);
        } else {
            post('/admin/promotion-popups', opts);
        }
    };

    return (
        <AdminLayout title={isEdit ? 'Edit Pop up' : 'Tambah Pop up'} breadcrumbs={[{ label: 'Pop up Promosi', href: '/admin/promotion-popups' }, { label: isEdit ? 'Edit' : 'Tambah' }]}>
            <Head title={isEdit ? 'Edit Pop up' : 'Tambah Pop up'} />
            <AdminPageHeader title={isEdit ? 'Edit Pop up Promosi' : 'Tambah Pop up Promosi'} backHref="/admin/promotion-popups" />
            <Card><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4 max-w-2xl">
                    <div><Label htmlFor="title">Judul</Label><Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required /></div>
                    <div>
                        <Label htmlFor="image">Gambar</Label>
                        <Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} required={!isEdit} />
                        {popup?.imageUrl && <div className="mt-2 flex items-center gap-3"><img src={popup.imageUrl} alt="" className="h-20 rounded" /><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_image} onChange={(e) => setData('remove_image', e.target.checked)} /> Hapus gambar</label></div>}
                    </div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="button_label">Label Tombol</Label><Input id="button_label" value={data.button_label} onChange={(e) => setData('button_label', e.target.value)} /></div>
                        <div><Label htmlFor="button_url">Link Tombol</Label><Input id="button_url" value={data.button_url} onChange={(e) => setData('button_url', e.target.value)} /></div>
                    </div>
                    <div><Label htmlFor="display_duration_seconds">Durasi Tampil (detik, 0 = manual close)</Label><Input id="display_duration_seconds" type="number" min={0} value={data.display_duration_seconds} onChange={(e) => setData('display_duration_seconds', Number(e.target.value))} /></div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="start_date">Mulai</Label><Input id="start_date" type="datetime-local" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} required /></div>
                        <div><Label htmlFor="end_date">Selesai</Label><Input id="end_date" type="datetime-local" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} required /></div>
                    </div>
                    <div>
                        <Label>Tampil di Halaman</Label>
                        <div className="grid grid-cols-2 gap-2 mt-2">
                            {Object.entries(pageOptions).map(([key, label]) => (
                                <label key={key} className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={data.show_on_pages.includes(key)} onChange={() => togglePage(key)} />
                                    {label}
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="priority">Priority</Label><Input id="priority" type="number" value={data.priority} onChange={(e) => setData('priority', Number(e.target.value))} /></div>
                        <label className="flex items-center gap-2 text-sm self-end pb-2"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    </div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/promotion-popups">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
