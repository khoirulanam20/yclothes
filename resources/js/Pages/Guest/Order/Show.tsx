import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type OrderItem = { productName: string; qty: number; unitPrice: number; subtotal: number };
type Order = {
    orderNumber: string; customerName: string; customerPhone: string; customerEmail: string;
    shippingAddress: string; shippingCity: string; shippingCost: number; totalPrice: number;
    grandTotal: number; paymentMethod: string; paymentStatus: string; orderStatus: string;
    courier?: string | null; trackingNumber?: string | null;
    bankName?: string | null; bankAccountNumber?: string | null; bankAccountName?: string | null;
    items: OrderItem[];
};

type Props = { order: Order };

export default function Show({ order }: Props) {
    return (
        <GuestLayout>
            <Head title={`Pesanan #${order.orderNumber}`} />
            <PageContainer narrow>
                <div className="flex justify-between items-center mb-4">
                    <h1 className="text-xl font-bold">Pesanan #{order.orderNumber}</h1>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/order/track">Lacak Lain</Link>
                    </Button>
                </div>

                <div className="flex gap-2 mb-4">
                    <Badge>{orderStatusLabels[order.orderStatus] ?? order.orderStatus}</Badge>
                    <Badge variant="outline">
                        {paymentStatusLabels[order.paymentStatus] ?? order.paymentStatus}
                    </Badge>
                </div>

                <SectionCard title="Detail Pengiriman" className="mb-4">
                    <div className="text-sm space-y-1">
                        <p>{order.customerName} — {order.customerPhone}</p>
                        <p className="text-muted-foreground">{order.shippingAddress}, {order.shippingCity}</p>
                        {order.courier && (
                            <p>Kurir: {order.courier}{order.trackingNumber && ` (Resi: ${order.trackingNumber})`}</p>
                        )}
                    </div>
                </SectionCard>

                <SectionCard title="Item Pesanan" noPadding className="mb-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead>Qty</TableHead>
                                <TableHead className="text-right">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {order.items.map((item, i) => (
                                <TableRow key={i}>
                                    <TableCell>{item.productName}</TableCell>
                                    <TableCell>{item.qty}</TableCell>
                                    <TableCell className="text-right">{formatRupiah(item.subtotal)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <div className="p-4 border-t space-y-1 text-sm">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span>{formatRupiah(order.totalPrice)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Ongkir</span>
                            <span>{formatRupiah(order.shippingCost)}</span>
                        </div>
                        <div className="flex justify-between font-bold text-base pt-1">
                            <span>Total</span>
                            <span className="text-primary">{formatRupiah(order.grandTotal)}</span>
                        </div>
                    </div>
                </SectionCard>

                {order.paymentMethod === 'bank_transfer' && order.bankName && (
                    <SectionCard title="Instruksi Transfer">
                        <p className="text-sm">{order.bankName} — {order.bankAccountNumber}</p>
                        <p className="text-sm text-muted-foreground">a.n. {order.bankAccountName}</p>
                    </SectionCard>
                )}
            </PageContainer>
        </GuestLayout>
    );
}
