import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { useAdminConfirm } from '@/components/admin/AdminConfirmProvider';
import { FieldError } from '@/components/admin/FieldError';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { orderStatusLabels, returnStatusLabels } from '@/lib/order-status';

type ReplacementOrder = {
    id: number; orderNumber: string; orderStatus: string;
    courier?: string | null; courierService?: string | null; trackingNumber?: string | null;
};

type ReturnRequest = {
    id: number; requestNumber: string; status: string; resolutionType?: string | null;
    adminNote?: string | null;
    items: { productName?: string; qty: number; reason: string; description?: string | null }[];
    media: { url: string; type: string }[];
    shipment?: { courier?: string; trackingNumber?: string; receivedAt?: string | null } | null;
    replacementOrder?: ReplacementOrder | null;
    order?: { orderNumber: string } | null;
};
type Props = { returnRequest: ReturnRequest };

export default function Show({ returnRequest }: Props) {
    const confirm = useAdminConfirm();
    const [rejectOpen, setRejectOpen] = useState(false);
    const [shipReplacementOpen, setShipReplacementOpen] = useState(false);
    const rejectForm = useForm({ admin_note: '' });
    const replacementShipForm = useForm({
        courier: returnRequest.replacementOrder?.courier ?? '',
        courier_service: returnRequest.replacementOrder?.courierService ?? '',
        tracking_number: returnRequest.replacementOrder?.trackingNumber ?? '',
    });

    const approve = async () => {
        if (await confirm({ title: 'Setujui Retur', description: 'Setujui pengajuan retur ini?' })) {
            router.post(`/admin/returns/${returnRequest.id}/approve`);
        }
    };

    const submitReject = async (e: React.FormEvent) => {
        e.preventDefault();
        if (rejectForm.data.admin_note.trim().length < 5) {
            return;
        }
        if (!await confirm({
            title: 'Tolak Retur',
            description: 'Retur akan ditolak dengan alasan yang Anda masukkan.',
            confirmLabel: 'Tolak',
            variant: 'destructive',
        })) {
            return;
        }
        rejectForm.post(`/admin/returns/${returnRequest.id}/reject`, {
            preserveScroll: true,
            onSuccess: () => {
                setRejectOpen(false);
                rejectForm.reset();
            },
        });
    };

    const resolve = async (type: 'refund' | 'replacement') => {
        if (await confirm({ title: type === 'refund' ? 'Refund Dana' : 'Ganti Barang', description: 'Selesaikan retur dengan opsi ini?' })) {
            router.post(`/admin/returns/${returnRequest.id}/resolve`, { resolution_type: type });
        }
    };

    const submitReplacementShip = (e: React.FormEvent) => {
        e.preventDefault();
        replacementShipForm.post(`/admin/returns/${returnRequest.id}/ship-replacement`, {
            preserveScroll: true,
            onSuccess: () => setShipReplacementOpen(false),
        });
    };

    return (
        <AdminLayout title={returnRequest.requestNumber} breadcrumbs={[{ label: 'Retur', href: '/admin/returns' }, { label: returnRequest.requestNumber }]}>
            <Head title="Detail Retur" />
            <AdminPageHeader title={returnRequest.requestNumber} backHref="/admin/returns" />

            <div className="grid lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-4">
                    <Card><CardHeader><CardTitle>Item</CardTitle></CardHeader><CardContent>
                        {returnRequest.items.map((item, i) => (
                            <div key={i} className="text-sm border-b py-2">
                                <p className="font-medium">{item.productName}</p>
                                <p>{item.qty}x · {item.reason}</p>
                                {item.description && <p className="text-muted-foreground">{item.description}</p>}
                            </div>
                        ))}
                    </CardContent></Card>

                    {returnRequest.media.length > 0 && (
                        <Card><CardHeader><CardTitle>Bukti</CardTitle></CardHeader><CardContent className="flex flex-wrap gap-2">
                            {returnRequest.media.map((m, i) => (
                                m.type === 'video'
                                    ? <video key={i} src={m.url} controls className="w-40 h-40 object-cover rounded" />
                                    : <img key={i} src={m.url} alt="" className="w-40 h-40 object-cover rounded" />
                            ))}
                        </CardContent></Card>
                    )}
                </div>

                <div className="space-y-4">
                    <Card><CardContent className="pt-6">
                        <Badge>{returnStatusLabels[returnRequest.status] ?? returnRequest.status}</Badge>
                        <p className="text-sm mt-2">Pesanan: {returnRequest.order?.orderNumber}</p>
                        {returnRequest.shipment && (
                            <p className="text-sm mt-1">Resi retur: {returnRequest.shipment.courier} — {returnRequest.shipment.trackingNumber}</p>
                        )}
                        {returnRequest.replacementOrder && (
                            <div className="mt-3 text-sm space-y-1 border-t pt-3">
                                <p className="font-medium">Pesanan Pengganti</p>
                                <p>#{returnRequest.replacementOrder.orderNumber}</p>
                                <p>{orderStatusLabels[returnRequest.replacementOrder.orderStatus] ?? returnRequest.replacementOrder.orderStatus}</p>
                                {returnRequest.replacementOrder.trackingNumber && (
                                    <p>Resi: {returnRequest.replacementOrder.courier} — {returnRequest.replacementOrder.trackingNumber}</p>
                                )}
                            </div>
                        )}
                        {returnRequest.status === 'rejected' && returnRequest.adminNote && (
                            <div className="mt-3 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm">
                                <p className="font-medium text-destructive">Alasan penolakan</p>
                                <p className="text-muted-foreground mt-1">{returnRequest.adminNote}</p>
                            </div>
                        )}
                    </CardContent></Card>

                    {returnRequest.status === 'pending_review' && (
                        <div className="flex flex-col gap-2">
                            <Button onClick={approve}>Setujui</Button>
                            <Button variant="destructive" onClick={() => setRejectOpen(true)}>Tolak</Button>
                        </div>
                    )}
                    {returnRequest.status === 'return_in_transit' && (
                        <Button onClick={() => router.post(`/admin/returns/${returnRequest.id}/received`)}>Konfirmasi Barang Diterima</Button>
                    )}
                    {returnRequest.status === 'received' && (
                        <div className="flex flex-col gap-2">
                            <Button onClick={() => resolve('refund')}>Refund Dana</Button>
                            <Button variant="outline" onClick={() => resolve('replacement')}>Ganti Barang</Button>
                        </div>
                    )}
                    {returnRequest.status === 'replacing' && returnRequest.replacementOrder?.orderStatus === 'processed' && (
                        <Button onClick={() => setShipReplacementOpen(true)}>Kirim Barang Pengganti</Button>
                    )}
                </div>
            </div>

            <Dialog open={shipReplacementOpen} onOpenChange={setShipReplacementOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Kirim Barang Pengganti</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitReplacementShip} className="space-y-3">
                        <div>
                            <Label htmlFor="replacement_courier">Kurir</Label>
                            <Input
                                id="replacement_courier"
                                value={replacementShipForm.data.courier}
                                onChange={(e) => replacementShipForm.setData('courier', e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="replacement_courier_service">Layanan</Label>
                            <Input
                                id="replacement_courier_service"
                                value={replacementShipForm.data.courier_service}
                                onChange={(e) => replacementShipForm.setData('courier_service', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label htmlFor="replacement_tracking_number">No. Resi</Label>
                            <Input
                                id="replacement_tracking_number"
                                value={replacementShipForm.data.tracking_number}
                                onChange={(e) => replacementShipForm.setData('tracking_number', e.target.value)}
                                required
                            />
                        </div>
                        <div className="flex gap-2 justify-end">
                            <Button type="button" variant="outline" onClick={() => setShipReplacementOpen(false)}>Batal</Button>
                            <Button type="submit" disabled={replacementShipForm.processing}>Simpan & Tandai Dikirim</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Retur</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitReject} className="space-y-3">
                        <div>
                            <Label htmlFor="admin_note">Alasan Penolakan</Label>
                            <Textarea
                                id="admin_note"
                                rows={4}
                                value={rejectForm.data.admin_note}
                                onChange={(e) => rejectForm.setData('admin_note', e.target.value)}
                                placeholder="Jelaskan alasan penolakan retur..."
                                required
                            />
                            <FieldError message={rejectForm.errors.admin_note} />
                        </div>
                        <div className="flex gap-2 justify-end">
                            <Button type="button" variant="outline" onClick={() => setRejectOpen(false)}>Batal</Button>
                            <Button type="submit" variant="destructive" disabled={rejectForm.processing}>
                                Konfirmasi Tolak
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
