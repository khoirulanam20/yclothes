import { Head, Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels } from '@/lib/order-status';

type Order = { id: number; orderNumber: string; grandTotal: number; orderStatus: string; createdAt?: string };
type Props = { orders: Order[] };

export default function Orders({ orders }: Props) {
    return (
        <AccountLayout title="Pesanan Saya">
            <Head title="Pesanan Saya" />
            {orders.length === 0 ? (
                <SectionCard className="text-center py-8">
                    <p className="text-muted-foreground">Belum ada pesanan.</p>
                </SectionCard>
            ) : (
                <div className="space-y-3">
                    {orders.map((order) => (
                        <SectionCard key={order.id} noPadding>
                            <div className="p-4 flex flex-wrap justify-between items-center gap-3">
                                <div>
                                    <p className="font-semibold text-sm">{order.orderNumber}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {order.createdAt && new Date(order.createdAt).toLocaleDateString('id-ID')}
                                    </p>
                                </div>
                                <Badge variant="secondary">
                                    {orderStatusLabels[order.orderStatus] ?? order.orderStatus}
                                </Badge>
                                <p className="font-bold text-primary text-sm">{formatRupiah(order.grandTotal)}</p>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/account/orders/${order.id}`}>Detail</Link>
                                </Button>
                            </div>
                        </SectionCard>
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}
