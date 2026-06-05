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

export default function Transfer({ products, warehouses }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        product_id: products[0]?.id ?? '',
        from_warehouse_id: warehouses[0]?.id ?? '',
        to_warehouse_id: warehouses[1]?.id ?? warehouses[0]?.id ?? '',
        quantity: 1,
        reason: '',
    });

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
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="product_id">Produk</Label>
                        <select id="product_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.product_id} onChange={(e) => setData('product_id', Number(e.target.value))} required>
                            {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select></div>
                    <div><Label htmlFor="from_warehouse_id">Dari Gudang</Label>
                        <select id="from_warehouse_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.from_warehouse_id} onChange={(e) => setData('from_warehouse_id', Number(e.target.value))} required>
                            {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select></div>
                    <div><Label htmlFor="to_warehouse_id">Ke Gudang</Label>
                        <select id="to_warehouse_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.to_warehouse_id} onChange={(e) => setData('to_warehouse_id', Number(e.target.value))} required>
                            {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                        </select><FieldError message={errors.to_warehouse_id} /></div>
                    <div><Label htmlFor="quantity">Jumlah</Label><Input id="quantity" type="number" min={1} value={data.quantity} onChange={(e) => setData('quantity', Number(e.target.value))} required /></div>
                    <div><Label htmlFor="reason">Alasan (opsional)</Label><Textarea id="reason" rows={2} value={data.reason} onChange={(e) => setData('reason', e.target.value)} /></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Transfer</Button><Button variant="outline" asChild><Link href="/admin/stock-movements">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
