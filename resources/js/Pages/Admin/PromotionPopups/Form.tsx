import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { LinkUrlField } from '@/components/admin/LinkUrlField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
            <AdminContent>
                <AdminPageHeader title={isEdit ? 'Edit Pop up Promosi' : 'Tambah Pop up Promosi'} backHref="/admin/promotion-popups" />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/promotion-popups">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="title">Judul</Label>
                                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="image">Gambar</Label>
                                <Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} required={!isEdit} />
                                {popup?.imageUrl && (
                                    <div className="mt-2 flex items-center gap-3">
                                        <img src={popup.imageUrl} alt="" className="h-20 rounded" />
                                        <label className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" checked={data.remove_image} onChange={(e) => setData('remove_image', e.target.checked)} />
                                            Hapus gambar
                                        </label>
                                    </div>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="button_label">Label Tombol</Label>
                                <Input id="button_label" value={data.button_label} onChange={(e) => setData('button_label', e.target.value)} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <LinkUrlField
                                    id="button_url"
                                    label="Link Tombol"
                                    value={data.button_url}
                                    onChange={(value) => setData('button_url', value)}
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="display_duration_seconds">Durasi Tampil (detik, 0 = manual close)</Label>
                                <Input id="display_duration_seconds" type="number" min={0} value={data.display_duration_seconds} onChange={(e) => setData('display_duration_seconds', Number(e.target.value))} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="start_date">Mulai</Label>
                                <Input id="start_date" type="datetime-local" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="end_date">Selesai</Label>
                                <Input id="end_date" type="datetime-local" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} required />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="priority">Priority</Label>
                                <Input id="priority" type="number" value={data.priority} onChange={(e) => setData('priority', Number(e.target.value))} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Tampil di Halaman</Label>
                                <div className="mt-2 grid grid-cols-2 gap-2">
                                    {Object.entries(pageOptions).map(([key, label]) => (
                                        <label key={key} className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" checked={data.show_on_pages.includes(key)} onChange={() => togglePage(key)} />
                                            {label}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </AdminFormGrid>
                        <AdminCheckboxRow
                            id="is_active"
                            label="Aktif"
                            checked={data.is_active}
                            onChange={(checked) => setData('is_active', checked)}
                        />
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
