import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type AttributeOption = { name: string; sortOrder?: number };
type Attribute = {
    id: number; name: string; code: string; type: string; isRequired?: boolean;
    isFilterable?: boolean; validation?: string | null; sortOrder?: number;
    options?: AttributeOption[];
};
type Props = { attribute?: Attribute; attributeTypes: string[] };

export default function Form({ attribute, attributeTypes }: Props) {
    const isEdit = !!attribute?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: attribute?.name ?? '',
        code: attribute?.code ?? '',
        type: attribute?.type ?? attributeTypes[0] ?? 'text',
        is_required: attribute?.isRequired ?? false,
        is_filterable: attribute?.isFilterable ?? false,
        validation: attribute?.validation ?? '',
        sort_order: attribute?.sortOrder ?? 0,
        options: attribute?.options?.map((o) => ({ name: o.name, sort_order: o.sortOrder ?? 0 })) ?? [] as AttributeOption[],
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/attributes/${attribute!.id}`);
        } else {
            post('/admin/attributes');
        }
    };

    const addOption = () => setData('options', [...data.options, { name: '', sort_order: 0 }]);
    const updateOption = (i: number, field: keyof AttributeOption, value: string | number) => {
        const opts = [...data.options];
        opts[i] = { ...opts[i], [field]: value };
        setData('options', opts);
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Atribut' : 'Tambah Atribut'}
            breadcrumbs={[
                { label: 'Atribut', href: '/admin/attributes' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Atribut' : 'Tambah Atribut'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Atribut' : 'Tambah Atribut'}
                backHref="/admin/attributes"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                        <div><Label htmlFor="code">Code</Label><Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value)} required /><FieldError message={errors.code} /></div>
                    </div>
                    <div><Label htmlFor="type">Tipe</Label>
                        <select id="type" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                            {attributeTypes.map((t) => <option key={t} value={t}>{t}</option>)}
                        </select></div>
                    <div className="flex gap-4">
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_required} onChange={(e) => setData('is_required', e.target.checked)} /> Required</label>
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_filterable} onChange={(e) => setData('is_filterable', e.target.checked)} /> Filterable</label>
                    </div>
                    {(data.type === 'select' || data.type === 'multiselect') && (
                        <div><Label>Options</Label><div className="space-y-2 mt-2">
                            {data.options.map((opt, i) => (
                                <div key={i} className="flex gap-2"><Input value={opt.name} onChange={(e) => updateOption(i, 'name', e.target.value)} placeholder="Nama option" /></div>
                            ))}</div>
                            <Button type="button" variant="outline" size="sm" className="mt-2" onClick={addOption}>+ Option</Button></div>
                    )}
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/attributes">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
