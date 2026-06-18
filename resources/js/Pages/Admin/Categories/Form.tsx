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

type Category = {
    id: number;
    name: string;
    slug: string;
    imageUrl?: string | null;
    order: number;
    parentId?: number | null;
};

type ParentOption = { id: number; name: string; depth: number };

type Props = {
    category?: Category;
    parentOptions?: ParentOption[];
    defaultParentId?: number | null;
};

function indentLabel(name: string, depth: number): string {
    return `${'— '.repeat(depth)}${name}`;
}

export default function Form({ category, parentOptions = [], defaultParentId = null }: Props) {
    const isEdit = !!category?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        parent_id: category?.parentId ?? defaultParentId ?? '',
        image: null as File | null,
        remove_image: false,
        order: category?.order ?? 0,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { forceFormData: true as const, preserveScroll: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/categories/${category!.id}`, options);
        } else {
            post('/admin/categories', options);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Kategori' : 'Tambah Kategori'}
            breadcrumbs={[
                { label: 'Kategori', href: '/admin/categories' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Kategori' : 'Tambah Kategori'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Kategori' : 'Tambah Kategori'}
                    backHref="/admin/categories"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/categories">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {isEdit ? 'Simpan Perubahan' : 'Simpan'}
                                </Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="parent_id">Kategori Induk</Label>
                                <select
                                    id="parent_id"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.parent_id}
                                    onChange={(e) =>
                                        setData('parent_id', e.target.value ? Number(e.target.value) : '')
                                    }
                                >
                                    <option value="">— Root (kategori utama) —</option>
                                    {parentOptions.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {indentLabel(option.name, option.depth)}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.parent_id} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="name">Nama Kategori</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <FieldError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="slug">Slug (kosongkan untuk otomatis)</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                />
                                <FieldError message={errors.slug} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="order">Urutan</Label>
                                <NumberInput
                                    id="order"
                                    min={0}
                                    value={data.order}
                                    onChange={(e) => setData('order', e)}
                                />
                                <FieldError message={errors.order} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="image">Gambar</Label>
                                <Input
                                    id="image"
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                    onChange={(e) => setData('image', e.target.files?.[0] ?? null)}
                                />
                                <FieldError message={errors.image} />
                                {isEdit && category?.imageUrl && (
                                    <div className="mt-2 flex items-center gap-3">
                                        <img src={category.imageUrl} alt="" className="h-16 rounded" />
                                        <label className="flex items-center gap-2 text-sm text-destructive">
                                            <input
                                                type="checkbox"
                                                checked={data.remove_image}
                                                onChange={(e) => setData('remove_image', e.target.checked)}
                                            />
                                            Hapus gambar
                                        </label>
                                    </div>
                                )}
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
