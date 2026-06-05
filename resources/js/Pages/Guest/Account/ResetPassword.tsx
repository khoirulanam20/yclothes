import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { AuthCard } from '@/components/storefront/AuthCard';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = { token: string; email?: string };

export default function ResetPassword({ token, email = '' }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    return (
        <GuestLayout>
            <Head title="Reset Password" />
            <AuthCard title="Reset Password">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/account/reset-password');
                    }}
                    className="space-y-4"
                >
                    <input type="hidden" name="token" value={data.token} />
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
                        <Label htmlFor="password">Password Baru</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
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
                        Reset Password
                    </Button>
                </form>
            </AuthCard>
        </GuestLayout>
    );
}
