import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { AuthCard } from '@/components/storefront/AuthCard';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    return (
        <GuestLayout>
            <Head title="Masuk" />
            <AuthCard title="Masuk">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/account/login');
                    }}
                    className="space-y-4"
                >
                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <FieldError message={errors.email} />
                    </div>
                    <div>
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        Ingat saya
                    </label>
                    <Button type="submit" className="w-full" disabled={processing}>
                        Masuk
                    </Button>
                    <p className="text-center text-sm text-muted-foreground">
                        Belum punya akun?{' '}
                        <Link href="/account/register" className="text-primary hover:underline">
                            Daftar
                        </Link>
                    </p>
                    <p className="text-center text-sm">
                        <Link href="/account/forgot-password" className="text-primary hover:underline">
                            Lupa password?
                        </Link>
                    </p>
                </form>
            </AuthCard>
        </GuestLayout>
    );
}
