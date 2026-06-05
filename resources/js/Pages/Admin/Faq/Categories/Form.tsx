import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type FaqCategory = { id: number; name: string; sortOrder?: number };
type Props = { category?: FaqCategory };

export default function Form({ category }: Props) {
    const isEdit = !!category?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: category?.name ?? '',
        sort_order: category?.sortOrder ?? 0,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/faq-categories/${category!.id}`);
        } else {
            post('/admin/faq-categories');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Kategori FAQ' : 'Tambah Kategori FAQ'}
            breadcrumbs={[
                { label: 'FAQ', href: '/admin/faq-categories' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Kategori FAQ' : 'Tambah Kategori FAQ'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Kategori FAQ' : 'Tambah Kategori FAQ'}
                backHref="/admin/faq-categories"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                    <div><Label htmlFor="sort_order">Urutan</Label><Input id="sort_order" type="number" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} /></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/faq-categories">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
