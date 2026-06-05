import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';

type Warehouse = { id: number; name: string; address?: string | null; city?: string | null; isActive?: boolean };
type Props = { warehouse?: Warehouse };

export default function Form({ warehouse }: Props) {
    const isEdit = !!warehouse?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: warehouse?.name ?? '',
        address: warehouse?.address ?? '',
        city: warehouse?.city ?? '',
        is_active: warehouse?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/warehouses/${warehouse!.id}`);
        } else {
            post('/admin/warehouses');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Gudang' : 'Tambah Gudang'}
            breadcrumbs={[
                { label: 'Gudang', href: '/admin/warehouses' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Gudang' : 'Tambah Gudang'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Gudang' : 'Tambah Gudang'}
                backHref="/admin/warehouses"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                    <div><Label htmlFor="address">Alamat</Label><Textarea id="address" rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} /></div>
                    <div><Label htmlFor="city">Kota</Label><Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} /></div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/warehouses">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
