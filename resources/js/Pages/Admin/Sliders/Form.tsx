import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type Slider = { id: number; title?: string | null; imageUrl: string; linkUrl?: string | null; sortOrder?: number; isActive?: boolean };
type Props = { slider?: Slider };

export default function Form({ slider }: Props) {
    const isEdit = !!slider?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        title: slider?.title ?? '',
        image: null as File | null,
        link_url: slider?.linkUrl ?? '',
        sort_order: slider?.sortOrder ?? 0,
        is_active: slider?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { forceFormData: true as const, preserveScroll: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/sliders/${slider!.id}`, options);
        } else {
            post('/admin/sliders', options);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Slider' : 'Tambah Slider'}
            breadcrumbs={[
                { label: 'Slider', href: '/admin/sliders' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Slider' : 'Tambah Slider'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Slider' : 'Tambah Slider'}
                backHref="/admin/sliders"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="title">Judul</Label><Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} /><FieldError message={errors.title} /></div>
                    <div><Label htmlFor="image">Gambar {!isEdit && '*'}</Label><Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />{isEdit && slider?.imageUrl && <img src={slider.imageUrl} alt="" className="h-20 mt-2 rounded" />}<FieldError message={errors.image} /></div>
                    <div><Label htmlFor="link_url">Link URL</Label><Input id="link_url" value={data.link_url} onChange={(e) => setData('link_url', e.target.value)} /></div>
                    <div><Label htmlFor="sort_order">Urutan</Label><Input id="sort_order" type="number" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} /></div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/sliders">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
