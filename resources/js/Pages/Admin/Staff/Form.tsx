import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Role = { id: number; name: string };
type Staff = { id: number; name: string; email: string; adminRoleId?: number | null; isAdmin?: boolean };
type Props = { staff?: Staff; roles: Role[] };

export default function Form({ staff, roles }: Props) {
    const isEdit = !!staff?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: staff?.name ?? '',
        email: staff?.email ?? '',
        password: '',
        password_confirmation: '',
        admin_role_id: staff?.adminRoleId ?? '',
        is_admin: staff?.isAdmin ?? false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/staff/${staff!.id}`);
        } else {
            post('/admin/staff');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Staff' : 'Tambah Staff'}
            breadcrumbs={[
                { label: 'Staff', href: '/admin/staff' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Staff' : 'Tambah Staff'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Staff' : 'Tambah Staff'}
                    backHref="/admin/staff"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/staff">Batal</Link>
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
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                                <FieldError message={errors.email} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="password">Password {isEdit && '(kosongkan jika tidak diubah)'}</Label>
                                <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                                <FieldError message={errors.password} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
                                <Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="admin_role_id">Role</Label>
                                <select
                                    id="admin_role_id"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.admin_role_id}
                                    onChange={(e) => setData('admin_role_id', e.target.value === '' ? '' : Number(e.target.value))}
                                >
                                    <option value="">— Pilih Role —</option>
                                    {roles.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                                </select>
                            </div>
                        </AdminFormGrid>
                        <AdminCheckboxRow
                            id="is_admin"
                            label="Super Admin"
                            checked={data.is_admin}
                            onChange={(checked) => setData('is_admin', checked)}
                        />
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
