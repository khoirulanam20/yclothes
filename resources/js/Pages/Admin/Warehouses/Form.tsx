import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Gudang' : 'Tambah Gudang'}
                    backHref="/admin/warehouses"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/warehouses">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="name">Nama</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                <FieldError message={errors.name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="city">Kota</Label>
                                <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="address">Alamat</Label>
                                <Textarea id="address" rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} />
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
