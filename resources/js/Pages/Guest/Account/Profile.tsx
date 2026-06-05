import { Head, useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { SectionCard } from '@/components/storefront/SectionCard';
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

    return (
        <AccountLayout title="Profil Saya">
            <Head title="Profil" />
            <SectionCard>
                <form onSubmit={submit} className="space-y-4 max-w-md">
                    {customer.avatarUrl && (
                        <img src={customer.avatarUrl} alt="" className="h-16 w-16 rounded-full object-cover" />
                    )}
                    <div>
                        <Label htmlFor="name">Nama</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        <FieldError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                        {!customer.emailVerified && (
                            <p className="text-xs text-amber-600 mt-1">Email belum diverifikasi</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="phone">Telepon</Label>
                        <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                    </div>
                    <div>
                        <Label htmlFor="avatar">Avatar</Label>
                        <Input
                            id="avatar"
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)}
                        />
                    </div>
                    <Button type="submit" disabled={processing}>
                        Simpan Profil
                    </Button>
                </form>
            </SectionCard>
        </AccountLayout>
    );
}
