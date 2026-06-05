import { Fragment, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FieldError } from '@/components/admin/FieldError';
import {
    ProductInventoryTable,
    buildInventoryRows,
    inventoryRowsToPayload,
    type InventoryRow,
} from '@/components/admin/ProductInventoryTable';

export type VariantRow = {
    id: number;
    sku: string;
    name?: string;
    price?: number | null;
    stock?: number;
    imageUrl?: string;
    attributes?: Record<string, string>;
    isActive?: boolean;
    inventories?: InventoryRow[];
};

type WarehouseOption = { id: number; name: string };

type Props = {
    productId: number;
    variants: VariantRow[];
    warehouses: WarehouseOption[];
    trackStock?: boolean;
};

type VariantFormRow = {
    id: number;
    sku: string;
    price: number | '';
    is_active: boolean;
    image: File | null;
    remove_image: boolean;
    inventories: InventoryRow[];
};

function buildVariantPayload(variant: VariantFormRow, hasFiles: boolean) {
    const inventories = inventoryRowsToPayload(variant.inventories);

    return {
        id: variant.id,
        sku: variant.sku,
        price: variant.price === '' ? null : Number(variant.price),
        is_active: variant.is_active,
        remove_image: variant.remove_image,
        ...(variant.image instanceof File ? { image: variant.image } : {}),
        ...(hasFiles
            ? { inventories_json: JSON.stringify(inventories) }
            : { inventories }),
    };
}

export function ProductVariantGrid({ productId, variants, warehouses, trackStock = true }: Props) {
    const [expanded, setExpanded] = useState<number[]>([]);
    const [submitting, setSubmitting] = useState(false);
    const pageErrors = (usePage().props.errors ?? {}) as Record<string, string>;

    const { data, setData, errors } = useForm({
        variants: variants.map((v) => ({
            id: v.id,
            sku: v.sku,
            price: v.price ?? '',
            is_active: v.isActive ?? true,
            image: null as File | null,
            remove_image: false,
            inventories: buildInventoryRows(warehouses, v.inventories),
        })),
    });

    const save = () => {
        const hasFiles = data.variants.some((variant) => variant.image instanceof File);
        const variantsPayload = data.variants.map((variant) =>
            buildVariantPayload(variant as VariantFormRow, hasFiles),
        );
        const url = `/admin/products/${productId}/variants`;
        const options = {
            preserveScroll: true,
            onStart: () => setSubmitting(true),
            onFinish: () => setSubmitting(false),
        };

        if (hasFiles) {
            router.post(
                url,
                { _method: 'put', variants: variantsPayload },
                { ...options, forceFormData: true },
            );
        } else {
            router.put(url, { variants: variantsPayload }, options);
        }
    };

    const fieldErrors: Record<string, string> = { ...pageErrors, ...errors };

    const toggleExpanded = (variantId: number) => {
        setExpanded((current) =>
            current.includes(variantId)
                ? current.filter((id) => id !== variantId)
                : [...current, variantId],
        );
    };

    if (variants.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Belum ada varian. Isi atribut Ukuran/Warna di tab Atribut lalu simpan produk.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            {Object.keys(fieldErrors).length > 0 && (
                <div className="rounded-md border border-destructive/40 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                    Periksa kembali data varian. Ada field yang belum valid.
                </div>
            )}

            <div className="overflow-x-auto rounded-md border">
                <table className="w-full min-w-[720px] text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Varian</th>
                            <th className="px-3 py-2 text-left">SKU</th>
                            <th className="px-3 py-2 text-left">Harga</th>
                            <th className="px-3 py-2 text-left">Total stok</th>
                            <th className="px-3 py-2 text-left">Gambar</th>
                            <th className="px-3 py-2 text-left">Aktif</th>
                            <th className="px-3 py-2 text-left" />
                        </tr>
                    </thead>
                    <tbody>
                        {variants.map((variant, index) => {
                            const row = data.variants[index];
                            const label =
                                [variant.attributes?.size, variant.attributes?.color]
                                    .filter(Boolean)
                                    .join(' / ') || variant.name;
                            const totalStock = row.inventories.reduce(
                                (sum, inventory) => sum + inventory.stock,
                                0,
                            );
                            const isExpanded = expanded.includes(variant.id);

                            return (
                                <Fragment key={variant.id}>
                                    <tr className="border-t">
                                        <td className="px-3 py-2">{label}</td>
                                        <td className="px-3 py-2">
                                            <Input
                                                value={row.sku}
                                                onChange={(e) => {
                                                    const next = [...data.variants];
                                                    next[index] = { ...next[index], sku: e.target.value };
                                                    setData('variants', next);
                                                }}
                                            />
                                            <FieldError message={fieldErrors[`variants.${index}.sku`]} />
                                        </td>
                                        <td className="px-3 py-2">
                                            <Input
                                                type="number"
                                                min={0}
                                                placeholder="Harga induk"
                                                value={row.price}
                                                onChange={(e) => {
                                                    const next = [...data.variants];
                                                    next[index] = {
                                                        ...next[index],
                                                        price:
                                                            e.target.value === ''
                                                                ? ''
                                                                : Number(e.target.value),
                                                    };
                                                    setData('variants', next);
                                                }}
                                            />
                                            <FieldError message={fieldErrors[`variants.${index}.price`]} />
                                        </td>
                                        <td className="px-3 py-2">
                                            <span className="font-medium">{totalStock}</span>
                                        </td>
                                        <td className="px-3 py-2">
                                            <Input
                                                type="file"
                                                accept="image/*"
                                                onChange={(e) => {
                                                    const next = [...data.variants];
                                                    next[index] = {
                                                        ...next[index],
                                                        image: e.target.files?.[0] ?? null,
                                                    };
                                                    setData('variants', next);
                                                }}
                                            />
                                            {variant.imageUrl && (
                                                <img
                                                    src={variant.imageUrl}
                                                    alt=""
                                                    className="mt-1 h-10 rounded"
                                                />
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            <input
                                                type="checkbox"
                                                checked={row.is_active}
                                                onChange={(e) => {
                                                    const next = [...data.variants];
                                                    next[index] = {
                                                        ...next[index],
                                                        is_active: e.target.checked,
                                                    };
                                                    setData('variants', next);
                                                }}
                                            />
                                        </td>
                                        <td className="px-3 py-2">
                                            {trackStock && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => toggleExpanded(variant.id)}
                                                >
                                                    {isExpanded ? 'Tutup stok' : 'Kelola stok'}
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                    {trackStock && isExpanded && (
                                        <tr className="border-t bg-muted/20">
                                            <td colSpan={7} className="px-3 py-3">
                                                <ProductInventoryTable
                                                    compact
                                                    rows={row.inventories}
                                                    warehouses={warehouses}
                                                    onChange={(inventories) => {
                                                        const next = [...data.variants];
                                                        next[index] = { ...next[index], inventories };
                                                        setData('variants', next);
                                                    }}
                                                    errors={fieldErrors}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <Button type="button" disabled={submitting} onClick={save}>
                {submitting ? 'Menyimpan...' : 'Simpan Varian'}
            </Button>
        </div>
    );
}
