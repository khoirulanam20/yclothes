import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { AuthCard } from '@/components/storefront/AuthCard';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function ForgotPassword() {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    return (
        <GuestLayout>
            <Head title="Lupa Password" />
            <AuthCard title="Lupa Password">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/account/forgot-password');
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
                    <Button type="submit" className="w-full" disabled={processing}>
                        Kirim Link Reset
                    </Button>
                    <p className="text-center text-sm">
                        <Link href="/account/login" className="text-primary hover:underline">
                            Kembali ke login
                        </Link>
                    </p>
                </form>
            </AuthCard>
        </GuestLayout>
    );
}
