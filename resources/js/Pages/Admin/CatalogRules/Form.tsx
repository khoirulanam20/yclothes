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
    slug?: string | null; metaTitle?: string | null; metaDescription?: string | null; bannerImageUrl?: string | null;
};
type Props = { rule?: CatalogRule; categoryOptions: CategoryOption[]; products: Product[] };

type NumberField = number | '';

function numberField(value: number | undefined | null): NumberField {
    if (value === undefined || value === null) {
        return '';
    }

    return value;
}

function normalizeNumbers<T extends Record<string, unknown>>(data: T): T {
    const numericKeys = ['discount_amount', 'priority', 'min_qty', 'buy_qty', 'get_qty', 'get_discount_percent'] as const;

    return {
        ...data,
        ...Object.fromEntries(
            numericKeys.map((key) => [
                key,
                data[key] === '' || data[key] === undefined ? null : Number(data[key]),
            ]),
        ),
    } as T;
}

export default function Form({ rule, categoryOptions, products }: Props) {
    const isEdit = !!rule?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        name: rule?.name ?? '',
        description: rule?.description ?? '',
        rule_type: rule?.ruleType ?? 'percentage_discount',
        discount_type: rule?.discountType ?? 'percentage',
        discount_amount: numberField(rule?.discountAmount),
        min_order_amount: rule?.minOrderAmount ?? '',
        min_qty: numberField(rule?.minQty),
        buy_qty: numberField(rule?.buyQty),
        get_qty: numberField(rule?.getQty),
        get_discount_percent: numberField(rule?.getDiscountPercent),
        category_ids: rule?.categoryIds ?? [] as number[],
        product_ids: rule?.productIds ?? [] as number[],
        start_date: rule?.startDate?.slice(0, 10) ?? '',
        end_date: rule?.endDate?.slice(0, 10) ?? '',
        is_active: rule?.isActive ?? true,
        priority: numberField(rule?.priority),
        slug: rule?.slug ?? '',
        meta_title: rule?.metaTitle ?? '',
        meta_description: rule?.metaDescription ?? '',
        banner_image: null as File | null,
        remove_banner_image: false,
    });

    const toggleCategory = (id: number) => {
        setData('category_ids', data.category_ids.includes(id) ? data.category_ids.filter((c) => c !== id) : [...data.category_ids, id]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const needsFormData = !!data.banner_image || (isEdit && data.remove_banner_image);
        const options = needsFormData ? { forceFormData: true as const } : {};
        if (isEdit) {
            transform((formData) => ({ ...normalizeNumbers(formData), _method: 'put' }));
            post(`/admin/catalog-rules/${rule!.id}`, options);
        } else {
            transform((formData) => normalizeNumbers(formData));
            post('/admin/catalog-rules', options);
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
                        <FieldError message={errors.category_ids} />
                    </div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>

                    <div className="border-t pt-4 space-y-4">
                        <h3 className="font-medium">SEO & Landing Page</h3>
                        <div><Label htmlFor="slug">Slug Landing (/promo/…)</Label><Input id="slug" value={data.slug} onChange={(e) => setData('slug', e.target.value)} placeholder="diskon-katalog" /></div>
                        <div><Label htmlFor="meta_title">Meta Title</Label><Input id="meta_title" value={data.meta_title} onChange={(e) => setData('meta_title', e.target.value)} /></div>
                        <div><Label htmlFor="meta_description">Meta Description</Label><Textarea id="meta_description" rows={2} value={data.meta_description} onChange={(e) => setData('meta_description', e.target.value)} /></div>
                        <div>
                            <Label htmlFor="banner_image">Banner Landing</Label>
                            <Input id="banner_image" type="file" accept="image/*" onChange={(e) => setData('banner_image', e.target.files?.[0] ?? null)} />
                            {rule?.bannerImageUrl && (
                                <div className="mt-2 flex items-center gap-3">
                                    <img src={rule.bannerImageUrl} alt="" className="h-16 rounded" />
                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_banner_image} onChange={(e) => setData('remove_banner_image', e.target.checked)} /> Hapus banner</label>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/catalog-rules">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
