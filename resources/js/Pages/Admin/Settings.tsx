import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    user: { name: string; email: string };
};

export default function Settings({ user }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/settings', { preserveScroll: true });
    };

    return (
        <AdminLayout title="Pengaturan" breadcrumbs={[{ label: 'Pengaturan' }]}>
            <Head title="Pengaturan" />
            <AdminContent>
                <AdminPageHeader title="Pengaturan" />
                <form onSubmit={submit}>
                    <AdminFormCard
                        footer={<Button type="submit" disabled={processing}>Simpan Pengaturan</Button>}
                    >
                        <h2 className="text-sm font-semibold mb-4">Profil Admin</h2>
                        <div data-tour="settings-profile">
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
                                <Label htmlFor="password">Password Baru</Label>
                                <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                                <FieldError message={errors.password} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
                                <Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                            </div>
                        </AdminFormGrid>
                        </div>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
