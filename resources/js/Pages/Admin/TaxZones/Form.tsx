import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type TaxRate = { id: number; name: string };
type TaxZone = { id: number; province?: string | null; city?: string | null; taxRateId?: number };
type Props = { zone?: TaxZone; taxRates: TaxRate[] };

export default function Form({ zone, taxRates }: Props) {
    const isEdit = !!zone?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        province: zone?.province ?? '',
        city: zone?.city ?? '',
        tax_rate_id: zone?.taxRateId ?? taxRates[0]?.id ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/tax-zones/${zone!.id}`);
        } else {
            post('/admin/tax-zones');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Zona Pajak' : 'Tambah Zona Pajak'}
            breadcrumbs={configurationSectionBreadcrumbs('Zona Pajak', '/admin/tax-zones', {
                label: isEdit ? 'Edit' : 'Tambah',
            })}
        >
            <Head title={isEdit ? 'Edit Zona Pajak' : 'Tambah Zona Pajak'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Zona Pajak' : 'Tambah Zona Pajak'}
                    backHref="/admin/tax-zones"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/tax-zones">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="province">Provinsi</Label>
                                <Input id="province" value={data.province} onChange={(e) => setData('province', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="city">Kota</Label>
                                <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="tax_rate_id">Tarif Pajak</Label>
                                <select id="tax_rate_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.tax_rate_id} onChange={(e) => setData('tax_rate_id', Number(e.target.value))} required>
                                    {taxRates.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                                </select>
                                <FieldError message={errors.tax_rate_id} />
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
