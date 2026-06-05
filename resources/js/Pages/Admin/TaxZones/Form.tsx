import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

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
            breadcrumbs={[
                { label: 'Zona Pajak', href: '/admin/tax-zones' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Zona Pajak' : 'Tambah Zona Pajak'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Zona Pajak' : 'Tambah Zona Pajak'}
                backHref="/admin/tax-zones"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="province">Provinsi</Label><Input id="province" value={data.province} onChange={(e) => setData('province', e.target.value)} /></div>
                    <div><Label htmlFor="city">Kota</Label><Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} /></div>
                    <div><Label htmlFor="tax_rate_id">Tarif Pajak</Label>
                        <select id="tax_rate_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.tax_rate_id} onChange={(e) => setData('tax_rate_id', Number(e.target.value))} required>
                            {taxRates.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                        </select><FieldError message={errors.tax_rate_id} /></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/tax-zones">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
