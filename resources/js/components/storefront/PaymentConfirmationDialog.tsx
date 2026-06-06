import { useForm } from '@inertiajs/react';
import { CopyAmount } from '@/components/storefront/CopyAmount';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/utils';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string };
type OrderInfo = {
    orderNumber: string;
    grandTotal: number;
    uniquePaymentAmount?: number | null;
    paymentMethod?: string;
    bankName?: string | null;
    bankAccountNumber?: string | null;
    bankAccountName?: string | null;
};
type QrisSettings = {
    imageUrl?: string | null;
    merchantName?: string | null;
    instructions?: string | null;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    order: OrderInfo;
    banks: Bank[];
    submitUrl: string;
    isQris?: boolean;
    qris?: QrisSettings | null;
};

export function PaymentConfirmationDialog({
    open, onOpenChange, order, banks, submitUrl, isQris = false, qris,
}: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        payment_bank_id: banks[0]?.id ?? '',
        amount_claimed: order.uniquePaymentAmount ?? order.grandTotal,
        transfer_date: new Date().toISOString().slice(0, 10),
        sender_name: '',
        proof_image: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(submitUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Pembayaran</DialogTitle>
                    <DialogDescription>Pesanan #{order.orderNumber}</DialogDescription>
                </DialogHeader>

                <div className="rounded-md border p-3 text-sm space-y-1 mb-2">
                    {isQris && qris ? (
                        <>
                            {qris.merchantName && <p className="font-medium">{qris.merchantName}</p>}
                            {qris.imageUrl && (
                                <img src={qris.imageUrl} alt="QRIS" className="mx-auto max-w-[180px] rounded border bg-white p-2 my-2" />
                            )}
                            {order.uniquePaymentAmount && (
                                <p className="text-primary font-semibold pt-1">
                                    Nominal unik:{' '}
                                    <CopyAmount amount={order.uniquePaymentAmount} />
                                </p>
                            )}
                            {qris.instructions && (
                                <p className="text-muted-foreground pt-1">{qris.instructions}</p>
                            )}
                        </>
                    ) : (
                        <>
                            <p>Transfer ke: <strong>{order.bankName}</strong> — {order.bankAccountNumber}</p>
                            <p className="text-muted-foreground">a.n. {order.bankAccountName}</p>
                            {order.uniquePaymentAmount && (
                                <p className="text-primary font-semibold pt-1">
                                    Nominal unik:{' '}
                                    <CopyAmount amount={order.uniquePaymentAmount} />
                                </p>
                            )}
                        </>
                    )}
                </div>

                <form onSubmit={submit} className="space-y-3">
                    {!isQris && banks.length > 0 && (
                        <div>
                            <Label>Rekening Tujuan</Label>
                            <select
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.payment_bank_id}
                                onChange={(e) => setData('payment_bank_id', Number(e.target.value))}
                            >
                                {banks.map((b) => (
                                    <option key={b.id} value={b.id}>{b.bankName} — {b.accountNumber}</option>
                                ))}
                            </select>
                        </div>
                    )}
                    <div>
                        <Label>Jumlah Dibayar</Label>
                        <Input
                            type="number"
                            value={data.amount_claimed}
                            onChange={(e) => setData('amount_claimed', Number(e.target.value))}
                            required
                        />
                        <FieldError message={errors.amount_claimed} />
                    </div>
                    <div>
                        <Label>Tanggal Transfer</Label>
                        <Input
                            type="date"
                            value={data.transfer_date}
                            onChange={(e) => setData('transfer_date', e.target.value)}
                            required
                        />
                    </div>
                    <div>
                        <Label>Nama Pengirim</Label>
                        <Input
                            value={data.sender_name}
                            onChange={(e) => setData('sender_name', e.target.value)}
                            required
                        />
                        <FieldError message={errors.sender_name} />
                    </div>
                    <div>
                        <Label>Bukti Transfer (opsional)</Label>
                        <Input
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('proof_image', e.target.files?.[0] ?? null)}
                        />
                    </div>
                    <div className="flex gap-2 pt-1">
                        <Button type="submit" disabled={processing} className="flex-1">
                            Kirim Konfirmasi
                        </Button>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                    </div>
                    {!order.uniquePaymentAmount && (
                        <p className="text-xs text-muted-foreground">
                            Total pesanan: {formatRupiah(order.grandTotal)}
                        </p>
                    )}
                </form>
            </DialogContent>
        </Dialog>
    );
}
