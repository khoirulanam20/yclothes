import { Head, Link, useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { SectionCard } from '@/components/storefront/SectionCard';
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
            <SectionCard className="mb-4">
                <p className="font-semibold">{returnRequest.requestNumber}</p>
                <p className="text-sm">Pesanan: {returnRequest.order?.orderNumber}</p>
                <p className="text-sm">{returnStatusLabels[returnRequest.status] ?? returnRequest.status}</p>
                {returnRequest.resolutionType === 'replacement' && (
                    <p className="text-sm text-muted-foreground mt-1">Resolusi: Ganti Barang</p>
                )}
                {returnRequest.adminNote && <p className="text-sm text-muted-foreground mt-2">{returnRequest.adminNote}</p>}
            </SectionCard>

            <SectionCard title="Item Retur" className="mb-4">
                {returnRequest.items.map((item, i) => (
                    <div key={i} className="text-sm border-b py-2 last:border-0">
                        <p className="font-medium">{item.productName}</p>
                        <p>Qty: {item.qty} · {item.reason}</p>
                        {item.description && <p className="text-muted-foreground">{item.description}</p>}
                    </div>
                ))}
            </SectionCard>

            {returnRequest.media.length > 0 && (
                <SectionCard title="Bukti" className="mb-4">
                    <div className="flex flex-wrap gap-2">
                        {returnRequest.media.map((m, i) => (
                            m.type === 'video'
                                ? <video key={i} src={m.url} controls className="w-32 h-32 object-cover rounded" />
                                : <img key={i} src={m.url} alt="" className="w-32 h-32 object-cover rounded" />
                        ))}
                    </div>
                </SectionCard>
            )}

            {returnRequest.status === 'awaiting_return_shipment' && (
                <SectionCard title="Kirim Barang Retur" className="mb-4">
                    <form onSubmit={(e) => { e.preventDefault(); shipForm.post(`/account/returns/${returnRequest.id}/shipment`); }} className="space-y-3">
                        <div><Label>Kurir</Label><Input value={shipForm.data.courier} onChange={(e) => shipForm.setData('courier', e.target.value)} required /></div>
                        <div><Label>No. Resi</Label><Input value={shipForm.data.tracking_number} onChange={(e) => shipForm.setData('tracking_number', e.target.value)} required /></div>
                        <Button type="submit" disabled={shipForm.processing}>Kirim Resi Retur</Button>
                    </form>
                </SectionCard>
            )}

            {returnRequest.shipment?.trackingNumber && (
                <SectionCard title="Resi Retur" className="mb-4">
                    <p className="text-sm">{returnRequest.shipment.courier} — {returnRequest.shipment.trackingNumber}</p>
                </SectionCard>
            )}

            {replacement && (
                <SectionCard title="Barang Pengganti" className="mb-4">
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
                </SectionCard>
            )}

            <Button variant="outline" asChild><Link href="/account/returns">Kembali</Link></Button>
        </AccountLayout>
    );
}
