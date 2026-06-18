import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { Label } from '@/components/ui/label';

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
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Kategori FAQ' : 'Tambah Kategori FAQ'}
                    backHref="/admin/faq-categories"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/faq-categories">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="name">Nama</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                <FieldError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sort_order">Urutan</Label>
                                <NumberInput id="sort_order" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', e)} />
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
