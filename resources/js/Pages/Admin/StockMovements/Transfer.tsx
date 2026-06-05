import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';

type VariantOption = { id: number; sku: string; label: string | null };
type ProductOption = {
    id: number;
    name: string;
    sku?: string | null;
    type: string;
    variants: VariantOption[];
};
type Warehouse = { id: number; name: string };
type Props = { products: ProductOption[]; warehouses: Warehouse[] };

export default function Transfer({ products, warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        product_id: number | '';
        product_variant_id: number | '';
        from_warehouse_id: number | '';
        to_warehouse_id: number | '';
        quantity: number;
        reason: string;
    }>({
        product_id: products[0]?.id ?? '',
        product_variant_id: '',
        from_warehouse_id: warehouses[0]?.id ?? '',
        to_warehouse_id: warehouses[1]?.id ?? warehouses[0]?.id ?? '',
        quantity: 1,
        reason: '',
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
        post('/admin/stock-movements/transfer', { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="Transfer Stok"
            breadcrumbs={[
                { label: 'Pergerakan Stok', href: '/admin/stock-movements' },
                { label: 'Transfer' },
            ]}
        >
            <Head title="Transfer Stok" />
            <AdminPageHeader title="Transfer Stok" backHref="/admin/stock-movements" />
            <Card className="max-w-xl">
                <CardContent className="p-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div>
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
                            <div>
                                <Label htmlFor="product_variant_id">Varian</Label>
                                {variantOptions.length === 0 ? (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Produk ini belum punya varian.
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

                        <div>
                            <Label htmlFor="from_warehouse_id">Dari Gudang</Label>
                            <select
                                id="from_warehouse_id"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={data.from_warehouse_id}
                                onChange={(e) => setData('from_warehouse_id', Number(e.target.value))}
                                required
                            >
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="to_warehouse_id">Ke Gudang</Label>
                            <select
                                id="to_warehouse_id"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={data.to_warehouse_id}
                                onChange={(e) => setData('to_warehouse_id', Number(e.target.value))}
                                required
                            >
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
                            </select>
                            <FieldError message={errors.to_warehouse_id} />
                        </div>
                        <div>
                            <Label htmlFor="quantity">Jumlah</Label>
                            <Input
                                id="quantity"
                                type="number"
                                min={1}
                                value={data.quantity}
                                onChange={(e) => setData('quantity', Number(e.target.value))}
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="reason">Alasan (opsional)</Label>
                            <Textarea
                                id="reason"
                                rows={2}
                                value={data.reason}
                                onChange={(e) => setData('reason', e.target.value)}
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={processing || (isConfigurable && variantOptions.length === 0)}
                            >
                                Transfer
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/admin/stock-movements">Batal</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
