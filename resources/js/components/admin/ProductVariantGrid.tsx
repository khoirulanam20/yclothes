import { Fragment, useEffect, useMemo, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { FieldError } from '@/components/admin/FieldError';
import {
    ProductGalleryField,
    type GalleryItem,
} from '@/components/admin/ProductGalleryField';
import {
    ProductInventoryTable,
    buildInventoryRows,
    inventoryRowsToPayload,
    type InventoryRow,
} from '@/components/admin/ProductInventoryTable';
import { getCsrfToken } from '@/lib/csrf';

export type VariantRow = {
    id: number;
    sku: string;
    name?: string;
    price?: number | null;
    stock?: number;
    imageUrl?: string;
    imagesUrl?: string[];
    imagesPaths?: string[];
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
    remove_image: boolean;
    inventories: InventoryRow[];
};

type VariantGalleryState = {
    items: GalleryItem[];
    removed: string[];
};

function buildInitialGallery(variant: VariantRow): VariantGalleryState {
    const paths = variant.imagesPaths ?? [];

    return {
        items: paths.map((path, index) => ({
            path,
            url: variant.imagesUrl?.[index] ?? variant.imageUrl ?? '',
        })),
        removed: [],
    };
}

function buildVariantPayload(
    variant: VariantFormRow,
    gallery: VariantGalleryState,
    useFormData: boolean,
) {
    const existingImages = gallery.items.filter((item) => !item.file).map((item) => item.path);
    const newImages = gallery.items.filter((item) => item.file).map((item) => item.file!);
    const inventories = inventoryRowsToPayload(variant.inventories);

    return {
        id: variant.id,
        sku: variant.sku,
        price: variant.price === '' ? null : Number(variant.price),
        is_active: variant.is_active,
        remove_image: variant.remove_image,
        existing_images: existingImages,
        new_images: newImages,
        remove_images: gallery.removed,
        inventories,
        inventories_json: useFormData ? JSON.stringify(inventories) : undefined,
    };
}

type VariantPayload = ReturnType<typeof buildVariantPayload>;

function newGalleryItemFromFile(file: File): GalleryItem {
    return {
        path: `new-${crypto.randomUUID()}`,
        url: '',
        file,
    };
}

function appendVariantFormData(formData: FormData, variant: VariantPayload, index: number) {
    formData.append(`variants[${index}][id]`, String(variant.id));
    formData.append(`variants[${index}][sku]`, variant.sku);
    if (variant.price !== null) {
        formData.append(`variants[${index}][price]`, String(variant.price));
    }
    formData.append(`variants[${index}][is_active]`, variant.is_active ? '1' : '0');
    if (variant.remove_image) {
        formData.append(`variants[${index}][remove_image]`, '1');
    }

    variant.existing_images.forEach((path) => {
        formData.append(`variants[${index}][existing_images][]`, path);
    });
    variant.remove_images.forEach((path) => {
        formData.append(`variants[${index}][remove_images][]`, path);
    });
    variant.new_images.forEach((file, fileIndex) => {
        formData.append(`variants[${index}][new_images][${fileIndex}]`, file);
    });

    if (variant.inventories_json) {
        formData.append(`variants[${index}][inventories_json]`, variant.inventories_json);
    }
}

function parseValidationErrors(payload: unknown): Record<string, string> {
    if (!payload || typeof payload !== 'object' || !('errors' in payload)) {
        return {};
    }

    const errors = (payload as { errors?: Record<string, string | string[]> }).errors ?? {};

    return Object.fromEntries(
        Object.entries(errors).map(([key, value]) => [
            key,
            Array.isArray(value) ? value[0] : String(value),
        ]),
    );
}

export function ProductVariantGrid({ productId, variants, warehouses, trackStock = true }: Props) {
    const [expandedStock, setExpandedStock] = useState<number[]>([]);
    const [expandedGallery, setExpandedGallery] = useState<number[]>([]);
    const [submitting, setSubmitting] = useState(false);
    const [saveErrors, setSaveErrors] = useState<Record<string, string>>({});
    const pageErrors = (usePage().props.errors ?? {}) as Record<string, string>;

    const initialGalleries = useMemo(
        () => Object.fromEntries(variants.map((variant) => [variant.id, buildInitialGallery(variant)])),
        [variants],
    );

    const variantImageSignature = useMemo(
        () =>
            variants
                .map((variant) => `${variant.id}:${(variant.imagesPaths ?? []).join('|')}`)
                .join(';'),
        [variants],
    );

    const [galleries, setGalleries] = useState<Record<number, VariantGalleryState>>(initialGalleries);

    useEffect(() => {
        setGalleries(initialGalleries);
    }, [variantImageSignature, initialGalleries]);

    const { data, setData, errors } = useForm({
        variants: variants.map((v) => ({
            id: v.id,
            sku: v.sku,
            price: v.price ?? '',
            is_active: v.isActive ?? true,
            remove_image: false,
            inventories: buildInventoryRows(warehouses, v.inventories),
        })),
    });

    const updateGallery = (variantId: number, updater: (current: VariantGalleryState) => VariantGalleryState) => {
        setGalleries((current) => {
            const before = current[variantId] ?? { items: [], removed: [] };
            const after = updater(before);
            return {
                ...current,
                [variantId]: after,
            };
        });
    };

    const save = async () => {
        const hasFiles = Object.values(galleries).some((gallery) =>
            gallery.items.some((item) => item.file instanceof File),
        );
        const needsFormData =
            hasFiles ||
            Object.values(galleries).some((gallery) => gallery.removed.length > 0);

        const variantsPayload = data.variants.map((variant) =>
            buildVariantPayload(
                variant as VariantFormRow,
                galleries[variant.id] ?? { items: [], removed: [] },
                needsFormData,
            ),
        );

        const url = `/admin/products/${productId}/variants`;
        setSubmitting(true);
        setSaveErrors({});

        try {
            if (needsFormData) {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                variantsPayload.forEach((variant, index) => {
                    appendVariantFormData(formData, variant, index);
                });

                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => null);
                    setSaveErrors(parseValidationErrors(payload));
                    return;
                }

                router.reload({ only: ['product'] });
                return;
            }

            await new Promise<void>((resolve) => {
                router.put(
                    url,
                    {
                        variants: variantsPayload.map(({ new_images: _files, inventories_json: _json, ...rest }) => rest),
                    },
                    {
                        preserveScroll: true,
                        onError: (errs) => setSaveErrors(errs),
                        onFinish: () => resolve(),
                    },
                );
            });
        } finally {
            setSubmitting(false);
        }
    };

    const fieldErrors: Record<string, string> = { ...pageErrors, ...errors, ...saveErrors };

    const toggleStock = (variantId: number) => {
        setExpandedStock((current) =>
            current.includes(variantId)
                ? current.filter((id) => id !== variantId)
                : [...current, variantId],
        );
    };

    const toggleGallery = (variantId: number) => {
        setExpandedGallery((current) =>
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
                <table className="w-full min-w-[820px] text-sm">
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
                            const gallery = galleries[variant.id] ?? { items: [], removed: [] };
                            const label =
                                [variant.attributes?.size, variant.attributes?.color]
                                    .filter(Boolean)
                                    .join(' / ') || variant.name;
                            const totalStock = row.inventories.reduce(
                                (sum, inventory) => sum + inventory.stock,
                                0,
                            );
                            const isStockExpanded = expandedStock.includes(variant.id);
                            const isGalleryExpanded = expandedGallery.includes(variant.id);
                            const thumbnail = gallery.items[0];

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
                                            <NumberInput
                                                min={0}
                                                placeholder="Harga induk"
                                                value={row.price}
                                                onChange={(e) => {
                                                    const next = [...data.variants];
                                                    next[index] = {
                                                        ...next[index],
                                                        price: e,
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
                                            <div className="flex items-center gap-2">
                                                {thumbnail ? (
                                                    <img
                                                        src={
                                                            thumbnail.file
                                                                ? URL.createObjectURL(thumbnail.file)
                                                                : thumbnail.url
                                                        }
                                                        alt=""
                                                        className="h-10 w-10 rounded object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex h-10 w-10 items-center justify-center rounded border border-dashed text-xs text-muted-foreground">
                                                        —
                                                    </div>
                                                )}
                                                {gallery.items.length > 1 && (
                                                    <span className="text-xs text-muted-foreground">
                                                        +{gallery.items.length - 1}
                                                    </span>
                                                )}
                                            </div>
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
                                            <div className="flex flex-wrap gap-1">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => toggleGallery(variant.id)}
                                                >
                                                    {isGalleryExpanded ? 'Tutup gambar' : 'Kelola gambar'}
                                                </Button>
                                                {trackStock && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => toggleStock(variant.id)}
                                                    >
                                                        {isStockExpanded ? 'Tutup stok' : 'Kelola stok'}
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                    {isGalleryExpanded && (
                                        <tr className="border-t bg-muted/20">
                                            <td colSpan={7} className="px-3 py-3">
                                                <ProductGalleryField
                                                    key={`variant-gallery-${variant.id}`}
                                                    compact
                                                    variantMode
                                                    mainImageFile={null}
                                                    removeMainImage={false}
                                                    onMainImageChange={() => undefined}
                                                    onRemoveMainImage={() => undefined}
                                                    gallery={gallery.items}
                                                    onAddGallery={(fileArray) => {
                                                        if (fileArray.length === 0) {
                                                            return;
                                                        }

                                                        updateGallery(variant.id, (current) => ({
                                                            ...current,
                                                            items: [
                                                                ...current.items,
                                                                ...fileArray.map((file) =>
                                                                    newGalleryItemFromFile(file),
                                                                ),
                                                            ],
                                                        }));
                                                    }}
                                                    onRemoveGallery={(path) => {
                                                        updateGallery(variant.id, (current) => ({
                                                            items: current.items.filter((item) => item.path !== path),
                                                            removed: path.startsWith('new-')
                                                                ? current.removed
                                                                : [...current.removed, path],
                                                        }));
                                                    }}
                                                    errors={{
                                                        new_images: fieldErrors[`variants.${index}.new_images`],
                                                    }}
                                                />
                                                <label className="mt-3 flex items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={row.remove_image}
                                                        onChange={(e) => {
                                                            const next = [...data.variants];
                                                            next[index] = {
                                                                ...next[index],
                                                                remove_image: e.target.checked,
                                                            };
                                                            setData('variants', next);
                                                        }}
                                                    />
                                                    Hapus semua gambar varian
                                                </label>
                                            </td>
                                        </tr>
                                    )}
                                    {trackStock && isStockExpanded && (
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
