import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type VariantOption = { id: number; sku: string; label: string | null };
type ProductOption = {
    id: number;
    name: string;
    sku?: string | null;
    type: string;
    variants: VariantOption[];
};
type Warehouse = { id: number; name: string };
type Inventory = {
    id: number;
    productId?: number;
    warehouseId?: number;
    stock: number;
    lowStockThreshold?: number;
    productVariantId?: number | null;
    displayName?: string;
    displaySku?: string | null;
};
type Props = { inventory?: Inventory; products: ProductOption[]; warehouses: Warehouse[] };

export default function Form({ inventory, products, warehouses }: Props) {
    const isEdit = !!inventory?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        product_id: inventory?.productId ?? products[0]?.id ?? '',
        product_variant_id: inventory?.productVariantId ?? '',
        warehouse_id: inventory?.warehouseId ?? warehouses[0]?.id ?? '',
        stock: inventory?.stock ?? 0,
        low_stock_threshold: inventory?.lowStockThreshold ?? 5,
    });

    const selectedProduct = useMemo(
        () => products.find((product) => product.id === Number(data.product_id)),
        [products, data.product_id],
    );

    const isConfigurable = selectedProduct?.type === 'configurable';
    const variantOptions = selectedProduct?.variants ?? [];

    const handleProductChange = (productId: number) => {
        const product = products.find((item) => item.id === productId);
        setData('product_id', productId);
        setData(
            'product_variant_id',
            product?.type === 'configurable' ? (product.variants[0]?.id ?? '') : '',
        );
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/inventories/${inventory!.id}`);
        } else {
            post('/admin/inventories');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Stok' : 'Tambah Stok'}
            breadcrumbs={[
                { label: 'Stok', href: '/admin/inventories' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Stok' : 'Tambah Stok'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Stok' : 'Tambah Stok'}
                    backHref="/admin/inventories"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/inventories">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing || (isConfigurable && variantOptions.length === 0 && !isEdit)}>
                                    Simpan
                                </Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            {isEdit ? (
                                <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                    <Label>Barang</Label>
                                    <p className="text-sm font-medium">{inventory?.displayName ?? '—'}</p>
                                    {inventory?.displaySku && (
                                        <p className="text-xs text-muted-foreground">SKU: {inventory.displaySku}</p>
                                    )}
                                </div>
                            ) : (
                                <>
                                    <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                        <Label htmlFor="product_id">Produk</Label>
                                        <select
                                            id="product_id"
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            value={data.product_id}
                                            onChange={(e) => handleProductChange(Number(e.target.value))}
                                            required
                                        >
                                            {products.map((product) => (
                                                <option key={product.id} value={product.id}>
                                                    {product.name}
                                                    {product.type === 'configurable' ? ' (varian)' : ''}
                                                    {product.sku ? ` — ${product.sku}` : ''}
                                                </option>
                                            ))}
                                        </select>
                                        <FieldError message={errors.product_id} />
                                    </div>

                                    {isConfigurable && (
                                        <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                            <Label htmlFor="product_variant_id">Varian</Label>
                                            {variantOptions.length === 0 ? (
                                                <p className="text-sm text-muted-foreground">
                                                    Produk ini belum punya varian. Buat varian di halaman edit produk
                                                    terlebih dahulu.
                                                </p>
                                            ) : (
                                                <select
                                                    id="product_variant_id"
                                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                    value={data.product_variant_id}
                                                    onChange={(e) =>
                                                        setData(
                                                            'product_variant_id',
                                                            e.target.value === '' ? '' : Number(e.target.value),
                                                        )
                                                    }
                                                    required
                                                >
                                                    <option value="">Pilih varian</option>
                                                    {variantOptions.map((variant) => (
                                                        <option key={variant.id} value={variant.id}>
                                                            {variant.label ?? variant.sku} — {variant.sku}
                                                        </option>
                                                    ))}
                                                </select>
                                            )}
                                            <FieldError message={errors.product_variant_id} />
                                        </div>
                                    )}
                                </>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="warehouse_id">Gudang</Label>
                                <select
                                    id="warehouse_id"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.warehouse_id}
                                    onChange={(e) => setData('warehouse_id', Number(e.target.value))}
                                    required
                                    disabled={isEdit}
                                >
                                    {warehouses.map((warehouse) => (
                                        <option key={warehouse.id} value={warehouse.id}>
                                            {warehouse.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="stock">Stok</Label>
                                <Input
                                    id="stock"
                                    type="number"
                                    min={0}
                                    value={data.stock}
                                    onChange={(e) => setData('stock', Number(e.target.value))}
                                    required
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="low_stock_threshold">Batas Stok Rendah</Label>
                                <Input
                                    id="low_stock_threshold"
                                    type="number"
                                    min={0}
                                    value={data.low_stock_threshold}
                                    onChange={(e) => setData('low_stock_threshold', Number(e.target.value))}
                                />
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
