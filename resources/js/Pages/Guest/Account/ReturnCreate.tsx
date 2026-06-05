import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import { SectionCard } from '@/components/storefront/SectionCard';
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

    return (
        <AccountLayout title="Ajukan Retur">
            <Head title="Ajukan Retur" />
            {policyText && <p className="text-sm text-muted-foreground mb-4">{policyText}</p>}

            {multiItem && (
                <p className="text-sm text-muted-foreground mb-4">
                    Pilih item yang ingin diretur dari pesanan ini.
                </p>
            )}

            <form onSubmit={submit} className="space-y-4">
                {returnableItems.map((item) => {
                    const idx = data.items.findIndex((row) => row.order_item_id === item.id);
                    const selected = selectedIds.includes(item.id);

                    return (
                        <SectionCard key={item.id} title={item.productName}>
                            {multiItem && (
                                <label className="flex items-center gap-2 text-sm mb-3">
                                    <input
                                        type="checkbox"
                                        checked={selected}
                                        onChange={(e) => toggleItem(item.id, e.target.checked)}
                                    />
                                    Retur item ini
                                </label>
                            )}

                            {(selected || !multiItem) && (
                                <div className="space-y-2">
                                    <div>
                                        <Label>Jumlah</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={item.maxQty}
                                            value={data.items[idx]?.qty ?? item.maxQty}
                                            onChange={(e) => updateItem(item.id, { qty: Number(e.target.value) })}
                                        />
                                    </div>
                                    <div>
                                        <Label>Alasan</Label>
                                        <select
                                            className="flex h-9 w-full rounded-md border px-3 text-sm"
                                            value={data.items[idx]?.reason ?? ''}
                                            onChange={(e) => updateItem(item.id, { reason: e.target.value })}
                                        >
                                            {returnReasons.map((reason) => (
                                                <option key={reason} value={reason}>{reason}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <Label>Keluhan</Label>
                                        <Textarea
                                            rows={3}
                                            value={data.items[idx]?.description ?? ''}
                                            onChange={(e) => updateItem(item.id, { description: e.target.value })}
                                        />
                                    </div>
                                </div>
                            )}
                        </SectionCard>
                    );
                })}

                <SectionCard title="Bukti Foto/Video">
                    <Input
                        type="file"
                        multiple
                        accept="image/*,video/*"
                        onChange={(e) => setData('media', Array.from(e.target.files ?? []))}
                    />
                </SectionCard>

                <div className="flex gap-2">
                    <Button
                        type="submit"
                        disabled={processing || returnableItems.length === 0 || selectedIds.length === 0}
                    >
                        Kirim Pengajuan
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/account/orders/${order.id}`}>Batal</Link>
                    </Button>
                </div>
            </form>
        </AccountLayout>
    );
}
