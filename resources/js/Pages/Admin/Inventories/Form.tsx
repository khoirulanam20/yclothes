import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type Product = { id: number; name: string };
type Warehouse = { id: number; name: string };
type Inventory = { id: number; productId?: number; warehouseId?: number; stock: number; lowStockThreshold?: number; productVariantId?: number | null };
type Props = { inventory?: Inventory; products: Product[]; warehouses: Warehouse[] };

export default function Form({ inventory, products, warehouses }: Props) {
    const isEdit = !!inventory?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        product_id: inventory?.productId ?? products[0]?.id ?? '',
        product_variant_id: inventory?.productVariantId ?? '',
        warehouse_id: inventory?.warehouseId ?? warehouses[0]?.id ?? '',
        stock: inventory?.stock ?? 0,
        low_stock_threshold: inventory?.lowStockThreshold ?? 5,
    });

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
            <AdminPageHeader
                title={isEdit ? 'Edit Stok' : 'Tambah Stok'}
                backHref="/admin/inventories"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="product_id">Produk</Label>
                        <select id="product_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.product_id} onChange={(e) => setData('product_id', Number(e.target.value))} required disabled={isEdit}>
                            {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select><FieldError message={errors.product_id} /></div>
                    <div><Label htmlFor="warehouse_id">Gudang</Label>
                        <select id="warehouse_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.warehouse_id} onChange={(e) => setData('warehouse_id', Number(e.target.value))} required disabled={isEdit}>
                            {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select></div>
                    <div><Label htmlFor="stock">Stok</Label><Input id="stock" type="number" min={0} value={data.stock} onChange={(e) => setData('stock', Number(e.target.value))} required /></div>
                    <div><Label htmlFor="low_stock_threshold">Low Stock Threshold</Label><Input id="low_stock_threshold" type="number" min={0} value={data.low_stock_threshold} onChange={(e) => setData('low_stock_threshold', Number(e.target.value))} /></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/inventories">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
