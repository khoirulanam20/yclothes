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

type Product = { id: number; name: string };
type CatalogRule = {
    id: number; name: string; description?: string | null; ruleType: string; discountType: string;
    discountAmount?: number; minOrderAmount?: number | null; minQty?: number | null;
    buyQty?: number | null; getQty?: number | null; getDiscountPercent?: number | null;
    startDate?: string; endDate?: string; isActive?: boolean; priority?: number;
    categoryIds?: number[]; productIds?: number[];
};
type Props = { rule?: CatalogRule; categoryOptions: CategoryOption[]; products: Product[] };

export default function Form({ rule, categoryOptions, products }: Props) {
    const isEdit = !!rule?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: rule?.name ?? '',
        description: rule?.description ?? '',
        rule_type: rule?.ruleType ?? 'percentage_discount',
        discount_type: rule?.discountType ?? 'percentage',
        discount_amount: rule?.discountAmount ?? 0,
        min_order_amount: rule?.minOrderAmount ?? '',
        min_qty: rule?.minQty ?? '',
        buy_qty: rule?.buyQty ?? '',
        get_qty: rule?.getQty ?? '',
        get_discount_percent: rule?.getDiscountPercent ?? '',
        category_ids: rule?.categoryIds ?? [] as number[],
        product_ids: rule?.productIds ?? [] as number[],
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
            post(`/admin/catalog-rules/${rule!.id}`);
        } else {
            post('/admin/catalog-rules');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Aturan Katalog' : 'Tambah Aturan Katalog'}
            breadcrumbs={[
                { label: 'Aturan Katalog', href: '/admin/catalog-rules' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Aturan Katalog' : 'Tambah Aturan Katalog'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Aturan Katalog' : 'Tambah Aturan Katalog'}
                backHref="/admin/catalog-rules"
            />
            <Card><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                    <div><Label htmlFor="description">Deskripsi</Label><Textarea id="description" rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="rule_type">Tipe Rule</Label>
                            <select id="rule_type" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.rule_type} onChange={(e) => setData('rule_type', e.target.value)}>
                                <option value="percentage_discount">Percentage Discount</option>
                                <option value="fixed_discount">Fixed Discount</option>
                                <option value="free_shipping_threshold">Free Shipping Threshold</option>
                                <option value="tiered_qty_discount">Tiered Qty Discount</option>
                                <option value="buy_x_get_y">Buy X Get Y</option>
                            </select></div>
                        <div><Label htmlFor="discount_type">Tipe Diskon</Label>
                            <select id="discount_type" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.discount_type} onChange={(e) => setData('discount_type', e.target.value)}>
                                <option value="percentage">Percentage</option><option value="fixed">Fixed</option>
                            </select></div>
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
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/catalog-rules">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
