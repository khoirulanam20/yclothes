import { Head, Link, useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { returnStatusLabels } from '@/lib/order-status';

type ReturnItem = { id: number; requestNumber: string; status: string; orderNumber?: string | null; createdAt: string };
type Props = { returns: ReturnItem[] };

export default function Returns({ returns }: Props) {
    return (
        <AccountLayout title="Retur Saya">
            <Head title="Retur" />
            {returns.length === 0 ? (
                <SectionCard className="text-center py-8 text-muted-foreground">Belum ada pengajuan retur.</SectionCard>
            ) : (
                <div className="space-y-3">
                    {returns.map((r) => (
                        <SectionCard key={r.id}>
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="font-semibold">{r.requestNumber}</p>
                                    <p className="text-sm text-muted-foreground">Pesanan {r.orderNumber}</p>
                                    <p className="text-sm">{returnStatusLabels[r.status] ?? r.status}</p>
                                </div>
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={`/account/returns/${r.id}`}>Detail</Link>
                                </Button>
                            </div>
                        </SectionCard>
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}
