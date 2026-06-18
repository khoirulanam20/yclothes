import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ShippingRegencySelect } from '@/components/admin/ShippingRegencySelect';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { Label } from '@/components/ui/label';
import { configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type Courier = { code: string; name: string };
type ShippingCost = {
    id: number;
    courierCode?: string | null;
    provinceCode?: string | null;
    provinceName?: string | null;
    regencyCode?: string | null;
    regencyName?: string | null;
    cost: number;
    costPerKg?: number | null;
    isActive?: boolean;
};
type Props = { cost?: ShippingCost; couriers: Courier[] };

export default function Form({ cost, couriers }: Props) {
    const isEdit = !!cost?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        courier_code: cost?.courierCode ?? '',
        province_code: cost?.provinceCode ?? '',
        province_name: cost?.provinceName ?? '',
        regency_code: cost?.regencyCode ?? '',
        regency_name: cost?.regencyName ?? '',
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
            breadcrumbs={configurationSectionBreadcrumbs('Tarif Ongkir Manual', '/admin/shipping-costs', {
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
                        <div className="space-y-2">
                            <Label htmlFor="courier_code">Jasa Ekspedisi</Label>
                            <select
                                id="courier_code"
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.courier_code}
                                onChange={(e) => setData('courier_code', e.target.value)}
                                required
                            >
                                <option value="">Pilih ekspedisi</option>
                                {couriers.map((c) => (
                                    <option key={c.code} value={c.code}>{c.name}</option>
                                ))}
                            </select>
                            <FieldError message={errors.courier_code} />
                        </div>

                        <ShippingRegencySelect
                            value={{
                                provinceCode: data.province_code,
                                provinceName: data.province_name,
                                regencyCode: data.regency_code,
                                regencyName: data.regency_name,
                            }}
                            onChange={(w) => setData({
                                ...data,
                                province_code: w.provinceCode,
                                province_name: w.provinceName,
                                regency_code: w.regencyCode,
                                regency_name: w.regencyName,
                            })}
                            errors={{
                                provinceCode: errors.province_code,
                                regencyCode: errors.regency_code,
                            }}
                        />

                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="cost">Ongkir Dasar</Label>
                                <NumberInput id="cost" min={0} value={data.cost} onChange={(e) => setData('cost', e)} required />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="cost_per_kg">Ongkir Per Kg (opsional)</Label>
                                <NumberInput id="cost_per_kg" min={0} value={data.cost_per_kg} onChange={(e) => setData('cost_per_kg', e)} />
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
