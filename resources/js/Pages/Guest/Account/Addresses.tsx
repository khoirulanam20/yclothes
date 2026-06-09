import { Head, Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageHeader } from '@/components/storefront/AccountPageHeader';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { GuestConfirmDeleteButton } from '@/components/guest/GuestConfirmDeleteButton';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Address = {
    id: number; label: string; recipientName: string; phone: string;
    streetAddress: string; city: string; province: string; isDefault?: boolean;
};
type Props = { addresses: Address[] };

export default function Addresses({ addresses }: Props) {
    return (
        <AccountLayout>
            <Head title="Alamat" />
            <AccountPageHeader
                title="Alamat Saya"
                action={
                    <Button size="sm" asChild>
                        <Link href="/account/addresses/create">Tambah Alamat</Link>
                    </Button>
                }
            />
            {addresses.length === 0 ? (
                <AccountPageShell title="Belum ada alamat">
                    <p className="py-6 text-center text-muted-foreground">Tambahkan alamat pengiriman untuk checkout lebih cepat.</p>
                </AccountPageShell>
            ) : (
                <div className="space-y-3">
                    {addresses.map((addr) => (
                        <AccountPageShell key={addr.id} noPadding>
                            <div className="p-4 sm:p-5">
                                <div className="mb-3 flex flex-wrap items-start justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="size-4 text-primary shrink-0" />
                                        <span className="font-semibold">{addr.label}</span>
                                        {addr.isDefault && <Badge className="text-xs">Utama</Badge>}
                                    </div>
                                    <div className="flex gap-1">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={`/account/addresses/${addr.id}/edit`}>Edit</Link>
                                        </Button>
                                        <GuestConfirmDeleteButton href={`/account/addresses/${addr.id}`} name={addr.label} variant="outline" />
                                    </div>
                                </div>
                                <p className="text-sm font-medium">{addr.recipientName} · {addr.phone}</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {addr.streetAddress}, {addr.city}, {addr.province}
                                </p>
                            </div>
                        </AccountPageShell>
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}
