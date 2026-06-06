import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard } from '@/components/admin/AdminContent';
import { AdminHelpPanel } from '@/components/admin/AdminHelpPanel';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { ProductRelationSection } from '@/components/admin/ProductRelationSection';
import { type SelectedProduct } from '@/components/admin/ProductSearchPicker';
import { ProductAttributeFields, type AttributeDefinition } from '@/components/admin/ProductAttributeFields';
import { ProductBadgeField, type BadgePresetValue } from '@/components/admin/ProductBadgeField';
import {
    ProductInventoryTable,
    buildInventoryRows,
    inventoryRowsToPayload,
    type InventoryRow,
} from '@/components/admin/ProductInventoryTable';
import { ProductGalleryField, type GalleryItem } from '@/components/admin/ProductGalleryField';
import { ProductVariantGrid, type VariantRow } from '@/components/admin/ProductVariantGrid';
import { RichTextEditor } from '@/components/admin/RichTextEditor';
import { CategorySelect, type CategoryOption } from '@/components/admin/CategorySelect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { productVariantHelp } from '@/lib/admin-help-content';
import { cn } from '@/lib/utils';

type Product = {
    id: number;
    name: string;
    slug: string;
    sku?: string;
    type?: string;
    description?: string | null;
    shortDescription?: string | null;
    price: number;
    salePrice?: number | null;
    salePriceStartsAt?: string | null;
    salePriceEndsAt?: string | null;
    imageUrl?: string;
    imagesUrl?: string[];
    imagesPaths?: string[];
    badge?: string | null;
    badgePreset?: BadgePresetValue;
    badgeColor?: string | null;
    weight?: number | null;
    categoryId?: number;
    attributeFamilyId?: number;
    isFeatured?: boolean;
    isActive?: boolean;
    trackStock?: boolean;
    allowBackorder?: boolean;
    isReturnable?: boolean;
    returnWindowDays?: number | null;
    warrantyDays?: number | null;
    metaTitle?: string | null;
    metaDescription?: string | null;
    metaKeywords?: string | null;
    variants?: VariantRow[];
    relatedProductIds?: number[];
    upSellProductIds?: number[];
    crossSellProductIds?: number[];
    relatedProducts?: SelectedProduct[];
    upSellProducts?: SelectedProduct[];
    crossSellProducts?: SelectedProduct[];
};

type Option = { id: number; name: string };
type TypeOption = { value: string; label: string };
type WarehouseOption = { id: number; name: string };

type ProductFormData = {
    category_id: number | string;
    attribute_family_id: number | string;
    type: string;
    sku: string;
    name: string;
    slug: string;
    short_description: string;
    description: string;
    price: number;
    sale_price: number | '';
    sale_price_starts_at: string;
    sale_price_ends_at: string;
    image: File | null;
    remove_image: boolean;
    existing_images: string[];
    new_images: File[];
    remove_images: string[];
    badge: string;
    badge_preset: BadgePresetValue;
    badge_color: string;
    inventories: InventoryRow[];
    weight: number | '';
    is_featured: boolean;
    is_active: boolean;
    track_stock: boolean;
    allow_backorder: boolean;
    is_returnable: boolean;
    return_window_days: number | '';
    warranty_days: number | '';
    meta_title: string;
    meta_description: string;
    meta_keywords: string;
    related_products: number[];
    up_sell_products: number[];
    cross_sell_products: number[];
    attributes: Record<string, string | number | boolean | string[] | { hex: string; name: string }[] | null>;
};

type Props = {
    product: Product;
    categoryOptions: CategoryOption[];
    attributeFamilyOptions: Option[];
    attributeDefinitions: AttributeDefinition[];
    attributeValues: Record<string, unknown>;
    productTypes: TypeOption[];
    badgePresets: Record<string, string>;
    warehouses: WarehouseOption[];
    inventoryRows: InventoryRow[];
    configurableWarning?: string | null;
    weightUnitLabel?: string;
};

const TABS = [
    { id: 'general', label: 'Umum' },
    { id: 'attributes', label: 'Atribut' },
    { id: 'description', label: 'Deskripsi' },
    { id: 'media', label: 'Media' },
    { id: 'price', label: 'Harga' },
    { id: 'shipping', label: 'Pengiriman & Stok' },
    { id: 'settings', label: 'Pengaturan' },
    { id: 'relations', label: 'Relasi' },
    { id: 'variants', label: 'Varian' },
] as const;

type TabId = (typeof TABS)[number]['id'];

export default function Edit({
    product,
    categoryOptions,
    attributeFamilyOptions,
    attributeDefinitions,
    attributeValues,
    productTypes,
    badgePresets,
    warehouses,
    inventoryRows,
    configurableWarning,
    weightUnitLabel = 'gram',
}: Props) {
    const [tab, setTab] = useState<TabId>('general');
    const [galleryItems, setGalleryItems] = useState<GalleryItem[]>(() =>
        (product.imagesPaths ?? []).map((path, i) => ({
            path,
            url: product.imagesUrl?.[i] ?? '',
        })),
    );
    const [removedGallery, setRemovedGallery] = useState<string[]>([]);
    const [relatedProducts, setRelatedProducts] = useState<SelectedProduct[]>(
        () => product.relatedProducts ?? [],
    );
    const [upSellProducts, setUpSellProducts] = useState<SelectedProduct[]>(
        () => product.upSellProducts ?? [],
    );
    const [crossSellProducts, setCrossSellProducts] = useState<SelectedProduct[]>(
        () => product.crossSellProducts ?? [],
    );

    const initialAttributes = useMemo(() => {
        const attrs: Record<string, unknown> = {};
        attributeDefinitions.forEach((def) => {
            attrs[def.code] = attributeValues[def.code] ?? null;
        });
        return attrs;
    }, [attributeDefinitions, attributeValues]);

    const { data, setData, post, transform, processing, errors } = useForm<ProductFormData>({
        category_id: product.categoryId ?? categoryOptions[0]?.id ?? '',
        attribute_family_id: product.attributeFamilyId ?? attributeFamilyOptions[0]?.id ?? '',
        type: product.type ?? 'simple',
        sku: product.sku ?? '',
        name: product.name ?? '',
        slug: product.slug ?? '',
        short_description: product.shortDescription ?? '',
        description: product.description ?? '',
        price: product.price ?? 0,
        sale_price: product.salePrice ?? '',
        sale_price_starts_at: product.salePriceStartsAt?.slice(0, 16) ?? '',
        sale_price_ends_at: product.salePriceEndsAt?.slice(0, 16) ?? '',
        image: null as File | null,
        remove_image: false,
        existing_images: product.imagesPaths ?? [],
        new_images: [] as File[],
        remove_images: [] as string[],
        badge: product.badge ?? '',
        badge_preset: product.badgePreset ?? 'none',
        badge_color: product.badgeColor ?? '',
        inventories: buildInventoryRows(warehouses, inventoryRows),
        weight: product.weight ?? '',
        is_featured: product.isFeatured ?? false,
        is_active: product.isActive ?? false,
        track_stock: product.trackStock ?? true,
        allow_backorder: product.allowBackorder ?? false,
        is_returnable: product.isReturnable ?? true,
        return_window_days: product.returnWindowDays ?? '',
        warranty_days: product.warrantyDays ?? '',
        meta_title: product.metaTitle ?? '',
        meta_description: product.metaDescription ?? '',
        meta_keywords: product.metaKeywords ?? '',
        related_products: product.relatedProductIds ?? [],
        up_sell_products: product.upSellProductIds ?? [],
        cross_sell_products: product.crossSellProductIds ?? [],
        attributes: initialAttributes as ProductFormData['attributes'],
    });

    const visibleTabs = TABS.filter((t) => t.id !== 'variants' || data.type === 'configurable');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const existingImages = galleryItems.filter((g) => !g.file).map((g) => g.path);
        const newImages = galleryItems.filter((g) => g.file).map((g) => g.file!);

        transform((d) => ({
            ...d,
            _method: 'put',
            existing_images: existingImages,
            new_images: newImages,
            remove_images: removedGallery,
            related_products: relatedProducts.map((item) => item.id),
            up_sell_products: upSellProducts.map((item) => item.id),
            cross_sell_products: crossSellProducts.map((item) => item.id),
            inventories: inventoryRowsToPayload(d.inventories),
        }));

        post(`/admin/products/${product.id}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const setAttribute = (code: string, value: unknown) => {
        setData('attributes', { ...data.attributes, [code]: value as ProductFormData['attributes'][string] });
    };

    const addGallery = (files: FileList | null) => {
        if (!files) return;
        const added = Array.from(files).map((file) => ({
            path: `new-${crypto.randomUUID()}`,
            url: '',
            file,
        }));
        setGalleryItems((current) => [...current, ...added]);
    };

    const removeGallery = (path: string) => {
        setGalleryItems((current) => current.filter((g) => g.path !== path));
        if (!path.startsWith('new-')) {
            setRemovedGallery((current) => [...current, path]);
        }
    };

    return (
        <AdminLayout
            title={`Edit ${product.name}`}
            breadcrumbs={[
                { label: 'Produk', href: '/admin/products' },
                { label: product.name },
            ]}
        >
            <Head title={`Edit ${product.name}`} />
            <AdminContent>
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <AdminPageHeader title={`Edit: ${product.name}`} backHref="/admin/products" />
                    {tab !== 'variants' && (
                        <Button type="submit" form="product-edit-form" disabled={processing}>
                            Simpan Produk
                        </Button>
                    )}
                </div>

            {configurableWarning && (
                <div className="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {configurableWarning}
                </div>
            )}

            <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
                {visibleTabs.map((t) => (
                    <Button
                        key={t.id}
                        type="button"
                        size="sm"
                        variant={tab === t.id ? 'default' : 'outline'}
                        onClick={() => setTab(t.id)}
                    >
                        {t.label}
                    </Button>
                ))}
            </div>

            <AdminFormCard>
                    <form id="product-edit-form" onSubmit={submit} className="space-y-4">
                        <div className={cn(tab !== 'general' && 'hidden')}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label>Kategori</Label>
                                    <CategorySelect
                                        value={data.category_id}
                                        options={categoryOptions}
                                        onChange={(value) => setData('category_id', value)}
                                        required
                                    />
                                    <FieldError message={errors.category_id} />
                                </div>
                                <div>
                                    <Label htmlFor="name">Nama</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <FieldError message={errors.name} />
                                </div>
                                <div>
                                    <Label htmlFor="slug">Slug</Label>
                                    <Input
                                        id="slug"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                    />
                                    <FieldError message={errors.slug} />
                                </div>
                                <div>
                                    <Label htmlFor="sku">SKU</Label>
                                    <Input
                                        id="sku"
                                        value={data.sku}
                                        onChange={(e) => setData('sku', e.target.value)}
                                        required
                                    />
                                    <FieldError message={errors.sku} />
                                </div>
                                <div>
                                    <Label htmlFor="type">Tipe</Label>
                                    <select
                                        id="type"
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                    >
                                        {productTypes.map((t) => (
                                            <option key={t.value} value={t.value}>
                                                {t.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label htmlFor="attribute_family_id">Keluarga Atribut</Label>
                                    <select
                                        id="attribute_family_id"
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        value={data.attribute_family_id}
                                        onChange={(e) =>
                                            setData('attribute_family_id', Number(e.target.value))
                                        }
                                    >
                                        {attributeFamilyOptions.map((f) => (
                                            <option key={f.id} value={f.id}>
                                                {f.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <ProductBadgeField
                                    preset={data.badge_preset}
                                    label={data.badge}
                                    color={data.badge_color}
                                    presetOptions={badgePresets}
                                    onChange={({ preset, label, color }) => {
                                        setData('badge_preset', preset);
                                        setData('badge', label);
                                        setData('badge_color', color);
                                    }}
                                    errors={{
                                        badge: errors.badge,
                                        badge_preset: errors.badge_preset,
                                        badge_color: errors.badge_color,
                                    }}
                                />
                            </div>
                        </div>

                        <div className={cn(tab !== 'attributes' && 'hidden')}>
                            <ProductAttributeFields
                                definitions={attributeDefinitions}
                                values={data.attributes}
                                onChange={setAttribute}
                                errors={errors}
                                productType={data.type}
                            />
                        </div>

                        <div className={cn(tab !== 'description' && 'hidden', 'space-y-4')}>
                            <div>
                                <Label htmlFor="short_description">Ringkasan</Label>
                                <Textarea
                                    id="short_description"
                                    rows={3}
                                    maxLength={500}
                                    value={data.short_description}
                                    onChange={(e) => setData('short_description', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>Deskripsi</Label>
                                <RichTextEditor
                                    value={data.description}
                                    onChange={(html) => setData('description', html)}
                                    minHeight={240}
                                />
                            </div>
                        </div>

                        <div className={cn(tab !== 'media' && 'hidden')}>
                            <ProductGalleryField
                                mainImageUrl={product.imageUrl}
                                mainImageFile={data.image}
                                removeMainImage={data.remove_image}
                                onMainImageChange={(file) => setData('image', file)}
                                onRemoveMainImage={(remove) => setData('remove_image', remove)}
                                gallery={galleryItems}
                                onAddGallery={addGallery}
                                onRemoveGallery={removeGallery}
                                errors={errors}
                            />
                        </div>

                        <div className={cn(tab !== 'price' && 'hidden', 'grid gap-4 md:grid-cols-3')}>
                            <div>
                                <Label htmlFor="price">Harga</Label>
                                <Input
                                    id="price"
                                    type="number"
                                    min={0}
                                    value={data.price}
                                    onChange={(e) => setData('price', Number(e.target.value))}
                                    required
                                />
                                <FieldError message={errors.price} />
                            </div>
                            <div>
                                <Label htmlFor="sale_price">Harga Spesial</Label>
                                <Input
                                    id="sale_price"
                                    type="number"
                                    min={0}
                                    value={data.sale_price}
                                    onChange={(e) =>
                                        setData(
                                            'sale_price',
                                            e.target.value === '' ? '' : Number(e.target.value),
                                        )
                                    }
                                />
                                <FieldError message={errors.sale_price} />
                            </div>
                            <div />
                            <div>
                                <Label htmlFor="sale_price_starts_at">Mulai Spesial</Label>
                                <Input
                                    id="sale_price_starts_at"
                                    type="datetime-local"
                                    value={data.sale_price_starts_at}
                                    onChange={(e) => setData('sale_price_starts_at', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="sale_price_ends_at">Selesai Spesial</Label>
                                <Input
                                    id="sale_price_ends_at"
                                    type="datetime-local"
                                    value={data.sale_price_ends_at}
                                    onChange={(e) => setData('sale_price_ends_at', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className={cn(tab !== 'shipping' && 'hidden', 'space-y-4')}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="weight">Berat ({weightUnitLabel ?? 'gram'})</Label>
                                    <Input
                                        id="weight"
                                        type="number"
                                        min={0}
                                        value={data.weight}
                                        onChange={(e) =>
                                            setData('weight', e.target.value === '' ? '' : Number(e.target.value))
                                        }
                                    />
                                </div>
                                <div className="flex flex-col gap-2 pt-6">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.track_stock}
                                            onChange={(e) => setData('track_stock', e.target.checked)}
                                        />
                                        Track Stock
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.allow_backorder}
                                            onChange={(e) => setData('allow_backorder', e.target.checked)}
                                        />
                                        Allow Backorder
                                    </label>
                                </div>
                            </div>

                            {data.type === 'simple' && data.track_stock && (
                                <ProductInventoryTable
                                    rows={data.inventories}
                                    warehouses={warehouses}
                                    onChange={(rows) => setData('inventories', rows)}
                                    errors={errors}
                                />
                            )}
                        </div>

                        <div className={cn(tab !== 'settings' && 'hidden', 'space-y-4')}>
                            <div className="flex flex-wrap gap-4">
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                    />
                                    Aktif (tampil di toko)
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.is_featured}
                                        onChange={(e) => setData('is_featured', e.target.checked)}
                                    />
                                    Featured
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.is_returnable}
                                        onChange={(e) => setData('is_returnable', e.target.checked)}
                                    />
                                    Bisa diretur
                                </label>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="return_window_days">Window Retur (hari)</Label>
                                    <Input
                                        id="return_window_days"
                                        type="number"
                                        min={0}
                                        value={data.return_window_days}
                                        onChange={(e) =>
                                            setData(
                                                'return_window_days',
                                                e.target.value === '' ? '' : Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="warranty_days">Garansi (hari)</Label>
                                    <Input
                                        id="warranty_days"
                                        type="number"
                                        min={0}
                                        value={data.warranty_days}
                                        onChange={(e) =>
                                            setData(
                                                'warranty_days',
                                                e.target.value === '' ? '' : Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="meta_title">Meta Title</Label>
                                    <Input
                                        id="meta_title"
                                        value={data.meta_title}
                                        onChange={(e) => setData('meta_title', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="meta_keywords">Meta Keywords</Label>
                                    <Input
                                        id="meta_keywords"
                                        value={data.meta_keywords}
                                        onChange={(e) => setData('meta_keywords', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="meta_description">Meta Description</Label>
                                <Textarea
                                    id="meta_description"
                                    rows={3}
                                    value={data.meta_description}
                                    onChange={(e) => setData('meta_description', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className={cn(tab !== 'relations' && 'hidden', 'space-y-4')}>
                            <ProductRelationSection
                                title="Produk Terkait"
                                description="Selain produk yang sedang dilihat pelanggan, mereka akan disajikan dengan produk terkait."
                                selected={relatedProducts}
                                onChange={setRelatedProducts}
                                excludeProductId={product.id}
                            />
                            <ProductRelationSection
                                title="Produk Up-Sell"
                                description="Pelanggan disajikan alternatif premium atau berkualitas lebih tinggi dari produk ini."
                                selected={upSellProducts}
                                onChange={setUpSellProducts}
                                excludeProductId={product.id}
                            />
                            <ProductRelationSection
                                title="Produk Cross-Sell"
                                description="Produk pelengkap yang ditampilkan di keranjang belanja."
                                selected={crossSellProducts}
                                onChange={setCrossSellProducts}
                                excludeProductId={product.id}
                            />
                        </div>

                        {tab !== 'variants' && (
                            <div className="flex gap-2 border-t pt-4">
                                <Button type="submit" disabled={processing}>
                                    Simpan Produk
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/products">Kembali</Link>
                                </Button>
                            </div>
                        )}
                    </form>

                    {tab === 'variants' && data.type === 'configurable' && (
                        <div className="space-y-4 border-t pt-4">
                            <AdminHelpPanel section={productVariantHelp} />
                            <ProductVariantGrid
                                productId={product.id}
                                variants={product.variants ?? []}
                                warehouses={warehouses}
                                trackStock={data.track_stock}
                            />
                            <div className="flex gap-2">
                                <Button variant="outline" asChild>
                                    <Link href="/admin/products">Kembali</Link>
                                </Button>
                            </div>
                        </div>
                    )}
            </AdminFormCard>
            </div>
            </AdminContent>
        </AdminLayout>
    );
}
