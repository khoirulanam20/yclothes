import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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
            <AdminPageHeader title="Pengaturan" />
            <form onSubmit={submit} className="space-y-6 max-w-xl">
                <Card>
                    <CardHeader><CardTitle>Profil Admin</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                        <div><Label htmlFor="email">Email</Label><Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required /><FieldError message={errors.email} /></div>
                        <div><Label htmlFor="password">Password Baru</Label><Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} /><FieldError message={errors.password} /></div>
                        <div><Label htmlFor="password_confirmation">Konfirmasi Password</Label><Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} /></div>
                    </CardContent>
                </Card>
                <Button type="submit" disabled={processing}>Simpan Pengaturan</Button>
            </form>
        </AdminLayout>
    );
}
