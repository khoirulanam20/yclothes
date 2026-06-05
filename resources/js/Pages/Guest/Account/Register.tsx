import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { AuthCard } from '@/components/storefront/AuthCard';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <GuestLayout>
            <Head title="Daftar" />
            <AuthCard title="Daftar Akun">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/account/register');
                    }}
                    className="space-y-4"
                >
                    <div>
                        <Label htmlFor="name">Nama</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        <FieldError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                        <FieldError message={errors.email} />
                    </div>
                    <div>
                        <Label htmlFor="phone">Telepon</Label>
                        <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                    </div>
                    <div>
                        <Label htmlFor="password">Password</Label>
                        <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                        <FieldError message={errors.password} />
                    </div>
                    <div>
                        <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />
                    </div>
                    <Button type="submit" className="w-full" disabled={processing}>
                        Daftar
                    </Button>
                    <p className="text-center text-sm text-muted-foreground">
                        Sudah punya akun?{' '}
                        <Link href="/account/login" className="text-primary hover:underline">
                            Masuk
                        </Link>
                    </p>
                </form>
            </AuthCard>
        </GuestLayout>
    );
}
