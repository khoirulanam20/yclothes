import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Slider = {
    id: number;
    title?: string | null;
    subtitle?: string | null;
    imageUrl: string;
    linkUrl?: string | null;
    ctaLabel?: string | null;
    sortOrder?: number;
    isActive?: boolean;
};
type Props = { slider?: Slider };

export default function Form({ slider }: Props) {
    const isEdit = !!slider?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        title: slider?.title ?? '',
        subtitle: slider?.subtitle ?? '',
        image: null as File | null,
        link_url: slider?.linkUrl ?? '',
        cta_label: slider?.ctaLabel ?? 'Jelajahi',
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
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Slider' : 'Tambah Slider'}
                    backHref="/admin/sliders"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/sliders">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                                <FieldError message={errors.title} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="subtitle">Subjudul</Label>
                                <Input id="subtitle" value={data.subtitle} onChange={(e) => setData('subtitle', e.target.value)} />
                                <FieldError message={errors.subtitle} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="link_url">Link URL</Label>
                                <Input id="link_url" value={data.link_url} onChange={(e) => setData('link_url', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="cta_label">Label Tombol CTA</Label>
                                <Input id="cta_label" value={data.cta_label} onChange={(e) => setData('cta_label', e.target.value)} placeholder="Jelajahi" />
                                <FieldError message={errors.cta_label} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="image">Gambar {!isEdit && '*'}</Label>
                                <Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />
                                {isEdit && slider?.imageUrl && <img src={slider.imageUrl} alt="" className="mt-2 h-20 rounded" />}
                                <FieldError message={errors.image} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sort_order">Urutan</Label>
                                <Input id="sort_order" type="number" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} />
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
