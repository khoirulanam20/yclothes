import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CategoryCheckboxList, type CategoryOption } from '@/components/admin/CategorySelect';
import { configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

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
            breadcrumbs={configurationSectionBreadcrumbs('Tarif Pajak', '/admin/tax-rates', {
                label: isEdit ? 'Edit' : 'Tambah',
            })}
        >
            <Head title={isEdit ? 'Edit Tarif Pajak' : 'Tambah Tarif Pajak'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Tarif Pajak' : 'Tambah Tarif Pajak'}
                    backHref="/admin/tax-rates"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/tax-rates">Batal</Link>
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
                            <div className="space-y-2">
                                <Label htmlFor="rate">Rate</Label>
                                <Input id="rate" type="number" min={0} step="0.01" value={data.rate} onChange={(e) => setData('rate', Number(e.target.value))} required />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type">Tipe</Label>
                                <select id="type" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Kategori</Label>
                                <CategoryCheckboxList
                                    options={categoryOptions}
                                    selectedIds={data.category_ids}
                                    onToggle={toggleCategory}
                                />
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
