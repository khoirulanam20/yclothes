import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CategoryCheckboxList, type CategoryOption } from '@/components/admin/CategorySelect';
import { Card, CardContent } from '@/components/ui/card';

type CartRule = {
    id: number; name: string; description?: string | null; couponCode?: string | null;
    discountType: string; discountAmount: number; minOrderAmount?: number | null;
    maxDiscount?: number | null; startDate?: string; endDate?: string; isActive?: boolean;
    priority?: number; usesPerCoupon?: number; usesPerCustomer?: number; categoryIds?: number[];
};
type Props = { rule?: CartRule; categoryOptions: CategoryOption[] };

export default function Form({ rule, categoryOptions }: Props) {
    const isEdit = !!rule?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: rule?.name ?? '',
        description: rule?.description ?? '',
        coupon_code: rule?.couponCode ?? '',
        uses_per_coupon: rule?.usesPerCoupon ?? 0,
        uses_per_customer: rule?.usesPerCustomer ?? 0,
        discount_type: rule?.discountType ?? 'percentage',
        discount_amount: rule?.discountAmount ?? 0,
        min_order_amount: rule?.minOrderAmount ?? '',
        max_discount: rule?.maxDiscount ?? '',
        category_ids: rule?.categoryIds ?? [] as number[],
        start_date: rule?.startDate?.slice(0, 10) ?? '',
        end_date: rule?.endDate?.slice(0, 10) ?? '',
        is_active: rule?.isActive ?? true,
        priority: rule?.priority ?? 0,
    });

    const toggleCategory = (id: number) => {
        setData('category_ids', data.category_ids.includes(id) ? data.category_ids.filter((c) => c !== id) : [...data.category_ids, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/cart-rules/${rule!.id}`);
        } else {
            post('/admin/cart-rules');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Aturan Keranjang' : 'Tambah Aturan Keranjang'}
            breadcrumbs={[
                { label: 'Aturan Keranjang', href: '/admin/cart-rules' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Aturan Keranjang' : 'Tambah Aturan Keranjang'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Aturan Keranjang' : 'Tambah Aturan Keranjang'}
                backHref="/admin/cart-rules"
            />
            <Card><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                        <div><Label htmlFor="coupon_code">Kode Kupon</Label><Input id="coupon_code" value={data.coupon_code} onChange={(e) => setData('coupon_code', e.target.value)} /></div>
                    </div>
                    <div><Label htmlFor="description">Deskripsi</Label><Textarea id="description" rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                    <div className="grid md:grid-cols-3 gap-4">
                        <div><Label htmlFor="discount_type">Tipe Diskon</Label>
                            <select id="discount_type" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.discount_type} onChange={(e) => setData('discount_type', e.target.value)}>
                                <option value="percentage">Percentage</option><option value="fixed">Fixed</option><option value="free_shipping">Free Shipping</option>
                            </select></div>
                        <div><Label htmlFor="discount_amount">Jumlah Diskon</Label><Input id="discount_amount" type="number" min={0} value={data.discount_amount} onChange={(e) => setData('discount_amount', Number(e.target.value))} required /></div>
                        <div><Label htmlFor="priority">Priority</Label><Input id="priority" type="number" value={data.priority} onChange={(e) => setData('priority', Number(e.target.value))} /></div>
                    </div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="start_date">Mulai</Label><Input id="start_date" type="date" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} required /></div>
                        <div><Label htmlFor="end_date">Selesai</Label><Input id="end_date" type="date" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} required /></div>
                    </div>
                    <div><Label>Kategori (opsional)</Label>
                        <CategoryCheckboxList
                            options={categoryOptions}
                            selectedIds={data.category_ids}
                            onToggle={toggleCategory}
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/cart-rules">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
