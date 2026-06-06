import { Head, useForm } from '@inertiajs/react';
import { User } from 'lucide-react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Customer = {
    id: number; name: string; email: string; phone?: string | null;
    avatarUrl?: string | null; emailVerified: boolean;
};
type Props = { customer: Customer };

export default function Profile({ customer }: Props) {
    const { data, setData, post, transform, processing, errors } = useForm({
        name: customer.name,
        email: customer.email,
        phone: customer.phone ?? '',
        avatar: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        transform((d) => ({ ...d, _method: 'put' }));
        post('/account/profile', { forceFormData: true, preserveScroll: true });
    };

    const avatarPreview = data.avatar
        ? URL.createObjectURL(data.avatar)
        : customer.avatarUrl;

    return (
        <AccountLayout title="Profil Saya">
            <Head title="Profil" />
            <AccountPageShell title="Informasi Akun" description="Perbarui data profil dan avatar Anda.">
                <form onSubmit={submit} className="max-w-2xl space-y-5">
                    <div className="flex items-center gap-4">
                        <div className="flex size-20 items-center justify-center overflow-hidden rounded-full border bg-muted">
                            {avatarPreview ? (
                                <img src={avatarPreview} alt="" className="size-full object-cover" />
                            ) : (
                                <User className="size-8 text-muted-foreground" />
                            )}
                        </div>
                        <div className="flex-1">
                            <Label htmlFor="avatar">Foto Profil</Label>
                            <Input
                                id="avatar"
                                type="file"
                                accept="image/*"
                                className="mt-1"
                                onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)}
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                            <FieldError message={errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="phone">Telepon</Label>
                            <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                        {!customer.emailVerified && (
                            <p className="mt-1 text-xs text-amber-600">Email belum diverifikasi</p>
                        )}
                        <FieldError message={errors.email} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan Profil
                    </Button>
                </form>
            </AccountPageShell>
        </AccountLayout>
    );
}
