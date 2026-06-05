import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

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
            <AdminPageHeader
                title={isEdit ? 'Edit Keluarga Atribut' : 'Tambah Keluarga Atribut'}
                backHref="/admin/attribute-families"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                    <div><Label>Atribut</Label><div className="grid gap-2 mt-2 max-h-60 overflow-y-auto border rounded-md p-3">
                        {attributes.map((a) => (
                            <label key={a.id} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.attribute_ids.includes(a.id)} onChange={() => toggle(a.id)} />{a.name} <code className="text-xs text-muted-foreground">({a.code})</code></label>
                        ))}</div></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/attribute-families">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
