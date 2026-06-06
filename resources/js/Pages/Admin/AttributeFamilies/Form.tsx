import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Attribute = { id: number; name: string; code: string };
type Family = { id: number; name: string };
type Props = { family?: Family; attributes: Attribute[]; selectedAttributeIds?: number[] };

export default function Form({ family, attributes, selectedAttributeIds = [] }: Props) {
    const isEdit = !!family?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: family?.name ?? '',
        attribute_ids: selectedAttributeIds as number[],
    });

    const toggle = (id: number) => {
        setData('attribute_ids', data.attribute_ids.includes(id) ? data.attribute_ids.filter((a) => a !== id) : [...data.attribute_ids, id]);
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
                                <div className="grid max-h-60 gap-2 overflow-y-auto rounded-md border p-3">
                                    {attributes.map((a) => (
                                        <label key={a.id} className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" checked={data.attribute_ids.includes(a.id)} onChange={() => toggle(a.id)} />
                                            {a.name} <code className="text-xs text-muted-foreground">({a.code})</code>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
