import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type ShippingCost = { id: number; cityName: string; cost: number; costPerKg?: number | null; isActive?: boolean };
type Props = { cost?: ShippingCost };

export default function Form({ cost }: Props) {
    const isEdit = !!cost?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        city_name: cost?.cityName ?? '',
        cost: cost?.cost ?? 0,
        cost_per_kg: cost?.costPerKg ?? '',
        is_active: cost?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/shipping-costs/${cost!.id}`);
        } else {
            post('/admin/shipping-costs');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Ongkir' : 'Tambah Ongkir'}
            breadcrumbs={configurationSectionBreadcrumbs('Ongkir', '/admin/shipping-costs', {
                label: isEdit ? 'Edit' : 'Tambah',
            })}
        >
            <Head title={isEdit ? 'Edit Ongkir' : 'Tambah Ongkir'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Ongkir' : 'Tambah Ongkir'}
                    backHref="/admin/shipping-costs"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/shipping-costs">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="city_name">Nama Kota</Label>
                                <Input id="city_name" value={data.city_name} onChange={(e) => setData('city_name', e.target.value)} required />
                                <FieldError message={errors.city_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="cost">Ongkir Dasar</Label>
                                <Input id="cost" type="number" min={0} value={data.cost} onChange={(e) => setData('cost', Number(e.target.value))} required />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="cost_per_kg">Ongkir Per Kg (opsional)</Label>
                                <Input id="cost_per_kg" type="number" min={0} value={data.cost_per_kg} onChange={(e) => setData('cost_per_kg', e.target.value === '' ? '' : Number(e.target.value))} />
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
