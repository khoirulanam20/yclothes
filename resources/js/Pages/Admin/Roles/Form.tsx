import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Role = { id: number; name: string; description?: string | null; permissions?: string[] };
type Props = { role?: Role; allPermissions: string[] };

export default function Form({ role, allPermissions }: Props) {
    const isEdit = !!role?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: role?.name ?? '',
        description: role?.description ?? '',
        permissions: role?.permissions ?? [] as string[],
    });

    const toggle = (perm: string) => {
        setData('permissions', data.permissions.includes(perm) ? data.permissions.filter((p) => p !== perm) : [...data.permissions, perm]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/roles/${role!.id}`);
        } else {
            post('/admin/roles');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Peran' : 'Tambah Peran'}
            breadcrumbs={[
                { label: 'Peran', href: '/admin/roles' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Peran' : 'Tambah Peran'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Peran' : 'Tambah Peran'}
                    backHref="/admin/roles"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/roles">Batal</Link>
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
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="description">Deskripsi</Label>
                                <Textarea id="description" rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Permissions</Label>
                                <div className="mt-2 grid max-h-60 grid-cols-2 gap-2 overflow-y-auto rounded-md border p-3">
                                    {allPermissions.map((p) => (
                                        <label key={p} className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" checked={data.permissions.includes(p)} onChange={() => toggle(p)} />
                                            {p}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
