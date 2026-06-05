import { Head, Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { SectionCard } from '@/components/storefront/SectionCard';
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
        <AccountLayout title="Alamat Saya">
            <Head title="Alamat" />
            <div className="flex justify-end mb-3">
                <Button size="sm" asChild>
                    <Link href="/account/addresses/create">Tambah Alamat</Link>
                </Button>
            </div>
            {addresses.length === 0 ? (
                <SectionCard className="text-center py-8">
                    <p className="text-muted-foreground">Belum ada alamat tersimpan.</p>
                </SectionCard>
            ) : (
                <div className="space-y-3">
                    {addresses.map((addr) => (
                        <SectionCard key={addr.id}>
                            <div className="flex justify-between items-start mb-2">
                                <div>
                                    <span className="font-semibold text-sm">{addr.label}</span>
                                    {addr.isDefault && <Badge className="ml-2 text-xs">Default</Badge>}
                                </div>
                                <div className="flex gap-1">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`/account/addresses/${addr.id}/edit`}>Edit</Link>
                                    </Button>
                                    <GuestConfirmDeleteButton href={`/account/addresses/${addr.id}`} name={addr.label} variant="outline" />
                                </div>
                            </div>
                            <p className="text-sm">{addr.recipientName} — {addr.phone}</p>
                            <p className="text-sm text-muted-foreground">
                                {addr.streetAddress}, {addr.city}, {addr.province}
                            </p>
                        </SectionCard>
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}
