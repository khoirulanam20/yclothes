import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Attribute = {
    id: number;
    name: string;
    code: string;
    type: string;
    canBeVariantAxis?: boolean;
};
type Family = { id: number; name: string };
type Props = {
    family?: Family;
    attributes: Attribute[];
    selectedAttributeIds?: number[];
    variantAxisIds?: number[];
};

export default function Form({
    family,
    attributes,
    selectedAttributeIds = [],
    variantAxisIds = [],
}: Props) {
    const isEdit = !!family?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: family?.name ?? '',
        attribute_ids: selectedAttributeIds as number[],
        variant_axis_ids: variantAxisIds as number[],
    });

    const toggleAttribute = (id: number) => {
        const included = data.attribute_ids.includes(id);
        if (included) {
            setData({
                attribute_ids: data.attribute_ids.filter((a) => a !== id),
                variant_axis_ids: data.variant_axis_ids.filter((a) => a !== id),
            });
        } else {
            setData('attribute_ids', [...data.attribute_ids, id]);
        }
    };

    const toggleVariantAxis = (id: number) => {
        setData(
            'variant_axis_ids',
            data.variant_axis_ids.includes(id)
                ? data.variant_axis_ids.filter((a) => a !== id)
                : [...data.variant_axis_ids, id],
        );
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/attribute-families/${family!.id}`);
        } else {
            post('/admin/attribute-families');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Keluarga Atribut' : 'Tambah Keluarga Atribut'}
            breadcrumbs={[
                { label: 'Atribut', href: '/admin/attribute-families' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Keluarga Atribut' : 'Tambah Keluarga Atribut'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Keluarga Atribut' : 'Tambah Keluarga Atribut'}
                    backHref="/admin/attribute-families"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/attribute-families">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="name">Nama</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                <FieldError message={errors.name} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Atribut</Label>
                                <div className="grid max-h-72 gap-2 overflow-y-auto rounded-md border p-3">
                                    {attributes.map((a) => {
                                        const selected = data.attribute_ids.includes(a.id);
                                        const variantAxis = data.variant_axis_ids.includes(a.id);

                                        return (
                                            <div
                                                key={a.id}
                                                className="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md border border-transparent px-1 py-1.5 text-sm"
                                            >
                                                <label className="flex min-w-0 flex-1 items-center gap-2">
                                                    <input
                                                        type="checkbox"
                                                        checked={selected}
                                                        onChange={() => toggleAttribute(a.id)}
                                                    />
                                                    <span>
                                                        {a.name}{' '}
                                                        <code className="text-xs text-muted-foreground">({a.code})</code>
                                                    </span>
                                                </label>
                                                {selected && a.canBeVariantAxis && (
                                                    <label className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <input
                                                            type="checkbox"
                                                            checked={variantAxis}
                                                            onChange={() => toggleVariantAxis(a.id)}
                                                        />
                                                        Menghasilkan varian
                                                    </label>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Centang &quot;Menghasilkan varian&quot; untuk atribut multiselect atau warna (color).
                                </p>
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
