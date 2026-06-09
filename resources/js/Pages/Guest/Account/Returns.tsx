import { Head, Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageHeader } from '@/components/storefront/AccountPageHeader';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { returnStatusLabels } from '@/lib/order-status';

type ReturnItem = { id: number; requestNumber: string; status: string; orderNumber?: string | null; createdAt: string };
type Props = { returns: ReturnItem[] };

export default function Returns({ returns }: Props) {
    return (
        <AccountLayout>
            <Head title="Retur" />
            <AccountPageHeader title="Retur Saya" />
            {returns.length === 0 ? (
                <AccountPageShell title="Belum ada retur">
                    <p className="py-6 text-center text-muted-foreground">Pengajuan retur akan muncul di sini.</p>
                </AccountPageShell>
            ) : (
                <div className="space-y-3">
                    {returns.map((r) => (
                        <AccountPageShell key={r.id} noPadding>
                            <div className="flex flex-wrap items-start justify-between gap-3 p-4 sm:p-5">
                                <div>
                                    <p className="font-semibold">{r.requestNumber}</p>
                                    <p className="mt-0.5 text-sm text-muted-foreground">Pesanan #{r.orderNumber}</p>
                                    <Badge variant="secondary" className="mt-2">
                                        {returnStatusLabels[r.status] ?? r.status}
                                    </Badge>
                                </div>
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={`/account/returns/${r.id}`}>Detail Retur</Link>
                                </Button>
                            </div>
                        </AccountPageShell>
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}
