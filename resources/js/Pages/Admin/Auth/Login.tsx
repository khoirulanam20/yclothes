import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { FieldError } from '@/components/admin/FieldError';
import type { SharedPageProps } from '@/types';

export default function Login() {
    const { theme } = usePage<SharedPageProps>().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/login');
    };

    return (
        <>
            <Head title="Login Admin" />
            <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4">
                <Card className="w-full max-w-md">
                    <CardContent className="p-6">
                        <div className="text-center mb-6">
                            {theme.brandLogo && (
                                <img src={theme.brandLogo} alt="" className="h-9 mx-auto mb-2" />
                            )}
                            <h1 className="font-serif text-xl font-bold text-primary">{theme.brandName} Admin</h1>
                            <p className="text-sm text-muted-foreground mt-1">Masuk untuk mengelola toko</p>
                        </div>

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoFocus
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
                                <FieldError message={errors.password} />
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
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
