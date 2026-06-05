import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CategoryCheckboxList, type CategoryOption } from '@/components/admin/CategorySelect';
import { Card, CardContent } from '@/components/ui/card';

type TaxRate = { id: number; name: string; rate: number; type: string; isActive?: boolean };
type Props = { rate?: TaxRate; categoryOptions: CategoryOption[]; selectedCategories?: number[] };

export default function Form({ rate, categoryOptions, selectedCategories = [] }: Props) {
    const isEdit = !!rate?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: rate?.name ?? '',
        rate: rate?.rate ?? 0,
        type: rate?.type ?? 'percentage',
        is_active: rate?.isActive ?? true,
        category_ids: selectedCategories as number[],
    });

    const toggleCategory = (id: number) => {
        setData('category_ids', data.category_ids.includes(id) ? data.category_ids.filter((c) => c !== id) : [...data.category_ids, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/tax-rates/${rate!.id}`);
        } else {
            post('/admin/tax-rates');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Tarif Pajak' : 'Tambah Tarif Pajak'}
            breadcrumbs={[
                { label: 'Tarif Pajak', href: '/admin/tax-rates' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Tarif Pajak' : 'Tambah Tarif Pajak'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Tarif Pajak' : 'Tambah Tarif Pajak'}
                backHref="/admin/tax-rates"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="rate">Rate</Label><Input id="rate" type="number" min={0} step="0.01" value={data.rate} onChange={(e) => setData('rate', Number(e.target.value))} required /></div>
                        <div><Label htmlFor="type">Tipe</Label>
                            <select id="type" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                <option value="percentage">Percentage</option><option value="fixed">Fixed</option>
                            </select></div>
                    </div>
                    <div><Label>Kategori</Label>
                        <CategoryCheckboxList
                            options={categoryOptions}
                            selectedIds={data.category_ids}
                            onToggle={toggleCategory}
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/tax-rates">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
