import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';

type Product = { id: number; name: string };
type Warehouse = { id: number; name: string };
type Props = { products: Product[]; warehouses: Warehouse[] };

export default function Adjustment({ products, warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        product_id: products[0]?.id ?? '',
        warehouse_id: warehouses[0]?.id ?? '',
        new_stock: 0,
        reason: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/stock-movements/adjustment', { preserveScroll: true });
    };

    return (
        <AdminLayout
            title="Penyesuaian Stok"
            breadcrumbs={[
                { label: 'Pergerakan Stok', href: '/admin/stock-movements' },
                { label: 'Penyesuaian' },
            ]}
        >
            <Head title="Penyesuaian Stok" />
            <AdminPageHeader title="Penyesuaian Stok" backHref="/admin/stock-movements" />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="product_id">Produk</Label>
                        <select id="product_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.product_id} onChange={(e) => setData('product_id', Number(e.target.value))} required>
                            {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select></div>
                    <div><Label htmlFor="warehouse_id">Gudang</Label>
                        <select id="warehouse_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.warehouse_id} onChange={(e) => setData('warehouse_id', Number(e.target.value))}>
                            <option value="">Default</option>
                            {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select></div>
                    <div><Label htmlFor="new_stock">Stok Baru</Label><Input id="new_stock" type="number" min={0} value={data.new_stock} onChange={(e) => setData('new_stock', Number(e.target.value))} required /><FieldError message={errors.new_stock} /></div>
                    <div><Label htmlFor="reason">Alasan</Label><Textarea id="reason" rows={3} value={data.reason} onChange={(e) => setData('reason', e.target.value)} required /><FieldError message={errors.reason} /></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/stock-movements">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
