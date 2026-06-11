import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus, Info } from 'lucide-react';
import { useMemo, useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageHeader } from '@/components/storefront/AccountPageHeader';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
type ReturnableItem = { id: number; productName: string; qty: number; maxQty: number };
type Props = {
    order: { id: number; orderNumber: string };
    returnableItems: ReturnableItem[];
    returnReasons: string[];
    policyText?: string | null;
};

type FormItem = { order_item_id: number; qty: number; reason: string; description: string };

type ItemFormProps = {
    item: ReturnableItem;
    formItem: FormItem | undefined;
    returnReasons: string[];
    onUpdate: (patch: Partial<FormItem>) => void;
};

function ReturnItemFormFields({ item, formItem, returnReasons, onUpdate }: ItemFormProps) {
    return (
        <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor={`qty-${item.id}`}>
                        Jumlah
                        <span className="ml-1 font-normal text-muted-foreground">(maks. {item.maxQty})</span>
                    </Label>
                    <Input
                        id={`qty-${item.id}`}
                        type="number"
                        min={1}
                        max={item.maxQty}
                        value={formItem?.qty ?? item.maxQty}
                        onChange={(e) => onUpdate({ qty: Number(e.target.value) })}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`reason-${item.id}`}>Alasan</Label>
                    <select
                        id={`reason-${item.id}`}
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        value={formItem?.reason ?? ''}
                        onChange={(e) => onUpdate({ reason: e.target.value })}
                    >
                        {returnReasons.map((reason) => (
                            <option key={reason} value={reason}>{reason}</option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor={`description-${item.id}`}>Keluhan</Label>
                <Textarea
                    id={`description-${item.id}`}
                    rows={4}
                    className="min-h-24 resize-y"
                    placeholder="Jelaskan kondisi barang atau masalah yang dialami..."
                    value={formItem?.description ?? ''}
                    onChange={(e) => onUpdate({ description: e.target.value })}
                />
            </div>
        </div>
    );
}

export default function ReturnCreate({ order, returnableItems, returnReasons, policyText }: Props) {
    const multiItem = returnableItems.length > 1;
    const [selectedIds, setSelectedIds] = useState<number[]>(
        multiItem ? [] : returnableItems.map((item) => item.id),
    );

    const initialItems = useMemo<FormItem[]>(
        () => returnableItems.map((item) => ({
            order_item_id: item.id,
            qty: item.maxQty,
            reason: returnReasons[0] ?? '',
            description: '',
        })),
        [returnableItems, returnReasons],
    );

    const { data, setData, processing } = useForm<{ items: FormItem[]; media: File[] }>({
        items: initialItems,
        media: [],
    });

    const toggleItem = (itemId: number, checked: boolean) => {
        setSelectedIds((current) => (
            checked ? [...current, itemId] : current.filter((id) => id !== itemId)
        ));
    };

    const updateItem = (itemId: number, patch: Partial<FormItem>) => {
        setData('items', data.items.map((item) => (
            item.order_item_id === itemId ? { ...item, ...patch } : item
        )));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const selectedItems = data.items.filter((item) => selectedIds.includes(item.order_item_id));
        if (selectedItems.length === 0) {
            return;
        }

        router.post(`/account/orders/${order.id}/returns`, {
            items: selectedItems,
            media: data.media,
        }, { forceFormData: true });
    };

    const canSubmit = !processing && returnableItems.length > 0 && selectedIds.length > 0;

    return (
        <AccountLayout>
            <Head title="Ajukan Retur" />

            <AccountPageHeader
                title="Ajukan Retur"
                action={
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/account/orders/${order.id}`}>
                            <ArrowLeft className="mr-1.5 size-4" />
                            Kembali ke Pesanan
                        </Link>
                    </Button>
                }
            />

            <p className="-mt-2 mb-4 text-sm text-muted-foreground">
                Pesanan <span className="font-medium text-foreground">#{order.orderNumber}</span>
            </p>

            {policyText && (
                <div className="mb-6 flex gap-3 rounded-xl border bg-muted/30 p-4 text-sm text-muted-foreground">
                    <Info className="mt-0.5 size-4 shrink-0 text-primary" />
                    <p className="leading-relaxed">{policyText}</p>
                </div>
            )}

            <form onSubmit={submit}>
                <AccountPageShell
                    title={multiItem ? 'Detail Retur' : (returnableItems[0]?.productName ?? 'Detail Retur')}
                    description={
                        multiItem
                            ? 'Pilih item yang ingin diretur, lalu isi detail keluhan.'
                            : `Maks. ${returnableItems[0]?.maxQty ?? 0} pcs bisa diretur dari pesanan ini.`
                    }
                >
                    <div className="space-y-6">
                        {multiItem ? (
                            <div className="divide-y rounded-xl border">
                                {returnableItems.map((item) => {
                                    const idx = data.items.findIndex((row) => row.order_item_id === item.id);
                                    const selected = selectedIds.includes(item.id);

                                    return (
                                        <div key={item.id} className="p-4 sm:p-5">
                                            <label className="flex cursor-pointer items-start gap-3">
                                                <input
                                                    type="checkbox"
                                                    className="mt-1 size-4 shrink-0 accent-primary"
                                                    checked={selected}
                                                    onChange={(e) => toggleItem(item.id, e.target.checked)}
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-medium leading-snug">{item.productName}</p>
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        Maks. {item.maxQty} pcs bisa diretur
                                                    </p>
                                                </div>
                                            </label>

                                            {selected && (
                                                <div className="mt-4 pl-7">
                                                    <ReturnItemFormFields
                                                        item={item}
                                                        formItem={data.items[idx]}
                                                        returnReasons={returnReasons}
                                                        onUpdate={(patch) => updateItem(item.id, patch)}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            returnableItems[0] && (
                                <ReturnItemFormFields
                                    item={returnableItems[0]}
                                    formItem={data.items[0]}
                                    returnReasons={returnReasons}
                                    onUpdate={(patch) => updateItem(returnableItems[0].id, patch)}
                                />
                            )
                        )}

                        <div className="space-y-2 border-t pt-6">
                            <Label htmlFor="return-media">Bukti Foto/Video</Label>
                            <div className="rounded-xl border border-dashed bg-muted/20 p-4">
                                <div className="flex flex-col items-center gap-2 text-center sm:flex-row sm:text-left">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-background text-muted-foreground">
                                        <ImagePlus className="size-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium">Unggah bukti pendukung</p>
                                        <p className="text-xs text-muted-foreground">
                                            Maks. 5 file · JPG, PNG, WEBP, MP4 · maks. 10 MB per file
                                        </p>
                                    </div>
                                    <Input
                                        id="return-media"
                                        type="file"
                                        multiple
                                        accept="image/*,video/*"
                                        className="max-w-full sm:max-w-xs"
                                        onChange={(e) => setData('media', Array.from(e.target.files ?? []))}
                                    />
                                </div>
                                {data.media.length > 0 && (
                                    <p className="mt-3 text-xs text-muted-foreground">
                                        {data.media.length} file dipilih
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                        <Button variant="outline" className="w-full sm:w-auto" asChild>
                            <Link href={`/account/orders/${order.id}`}>Batal</Link>
                        </Button>
                        <Button type="submit" className="w-full sm:w-auto" disabled={!canSubmit}>
                            Kirim Pengajuan
                        </Button>
                    </div>
                </AccountPageShell>
            </form>
        </AccountLayout>
    );
}
