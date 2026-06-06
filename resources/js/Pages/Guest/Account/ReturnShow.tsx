import { Head, Link, useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { orderStatusLabels, returnStatusLabels } from '@/lib/order-status';

type ReplacementOrder = {
    id: number;
    orderNumber: string;
    orderStatus: string;
    courier?: string | null;
    courierService?: string | null;
    trackingNumber?: string | null;
};

type ReturnRequest = {
    id: number; requestNumber: string; status: string; resolutionType?: string | null;
    adminNote?: string | null;
    items: { productName?: string; qty: number; reason: string; description?: string | null }[];
    media: { url: string; type: string }[];
    shipment?: { courier?: string; trackingNumber?: string } | null;
    replacementOrder?: ReplacementOrder | null;
    order?: { orderNumber: string } | null;
};
type Props = { returnRequest: ReturnRequest };

export default function ReturnShow({ returnRequest }: Props) {
    const shipForm = useForm({ courier: '', tracking_number: '' });
    const replacement = returnRequest.replacementOrder;

    return (
        <AccountLayout title={`Retur ${returnRequest.requestNumber}`}>
            <Head title="Detail Retur" />

            <AccountPageShell
                className="mb-4"
                title={returnRequest.requestNumber}
                description={`Pesanan #${returnRequest.order?.orderNumber}`}
                actions={
                    <Badge variant="secondary">
                        {returnStatusLabels[returnRequest.status] ?? returnRequest.status}
                    </Badge>
                }
            >
                {returnRequest.resolutionType === 'replacement' && (
                    <p className="text-sm text-muted-foreground">Resolusi: Ganti Barang</p>
                )}
                {returnRequest.adminNote && (
                    <p className="mt-2 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                        {returnRequest.adminNote}
                    </p>
                )}
            </AccountPageShell>

            <AccountPageShell title="Item Retur" className="mb-4">
                <div className="divide-y">
                    {returnRequest.items.map((item, i) => (
                        <div key={i} className="py-3 text-sm first:pt-0 last:pb-0">
                            <p className="font-medium">{item.productName}</p>
                            <p className="text-muted-foreground">Qty: {item.qty} · {item.reason}</p>
                            {item.description && <p className="mt-1 text-muted-foreground">{item.description}</p>}
                        </div>
                    ))}
                </div>
            </AccountPageShell>

            {returnRequest.media.length > 0 && (
                <AccountPageShell title="Bukti Pengajuan" className="mb-4">
                    <div className="flex flex-wrap gap-2">
                        {returnRequest.media.map((m, i) => (
                            m.type === 'video' ? (
                                <video key={i} src={m.url} controls className="size-28 rounded-lg border object-cover" />
                            ) : (
                                <img key={i} src={m.url} alt="" className="size-28 rounded-lg border object-cover" />
                            )
                        ))}
                    </div>
                </AccountPageShell>
            )}

            {returnRequest.status === 'awaiting_return_shipment' && (
                <AccountPageShell title="Kirim Barang Retur" className="mb-4">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            shipForm.post(`/account/returns/${returnRequest.id}/shipment`);
                        }}
                        className="grid gap-3 sm:grid-cols-2"
                    >
                        <div>
                            <Label>Kurir</Label>
                            <Input value={shipForm.data.courier} onChange={(e) => shipForm.setData('courier', e.target.value)} required />
                        </div>
                        <div>
                            <Label>No. Resi</Label>
                            <Input value={shipForm.data.tracking_number} onChange={(e) => shipForm.setData('tracking_number', e.target.value)} required />
                        </div>
                        <div className="sm:col-span-2">
                            <Button type="submit" disabled={shipForm.processing}>Kirim Resi Retur</Button>
                        </div>
                    </form>
                </AccountPageShell>
            )}

            {returnRequest.shipment?.trackingNumber && (
                <AccountPageShell title="Resi Retur" className="mb-4">
                    <p className="text-sm">
                        {returnRequest.shipment.courier} — <span className="font-medium">{returnRequest.shipment.trackingNumber}</span>
                    </p>
                </AccountPageShell>
            )}

            {replacement && (
                <AccountPageShell title="Barang Pengganti" className="mb-4">
                    <p className="text-sm mb-2">
                        Pesanan pengganti: <span className="font-medium">#{replacement.orderNumber}</span>
                    </p>
                    <p className="text-sm mb-3">
                        Status: {orderStatusLabels[replacement.orderStatus] ?? replacement.orderStatus}
                    </p>
                    {replacement.trackingNumber && (
                        <p className="text-sm mb-3">
                            Resi: {replacement.courier}{replacement.courierService ? ` · ${replacement.courierService}` : ''} — {replacement.trackingNumber}
                        </p>
                    )}
                    {replacement.orderStatus === 'processed' && (
                        <p className="text-sm text-muted-foreground mb-3">Menunggu penjual mengirim barang pengganti.</p>
                    )}
                    {['shipped', 'delivered', 'completed'].includes(replacement.orderStatus) && (
                        <Button asChild>
                            <Link href={`/account/orders/${replacement.id}`}>
                                {replacement.orderStatus === 'completed' ? 'Lihat Pesanan & Ulasan' : 'Lacak & Konfirmasi Terima'}
                            </Link>
                        </Button>
                    )}
                </AccountPageShell>
            )}

            <Button variant="outline" asChild><Link href="/account/returns">Kembali ke Daftar Retur</Link></Button>
        </AccountLayout>
    );
}
