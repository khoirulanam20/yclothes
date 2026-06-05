import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CategorySelect, type CategoryOption } from '@/components/admin/CategorySelect';
import { Card, CardContent } from '@/components/ui/card';

type Product = {
    id: number; name: string; slug: string; description?: string | null; price: number;
    salePrice?: number | null; imageUrl: string; badge?: string | null; weight?: number | null;
    categoryId?: number; isFeatured?: boolean; trackStock?: boolean; allowBackorder?: boolean;
    sizes?: string[]; colors?: string[];
};

type Props = { product?: Product; categoryOptions: CategoryOption[] };

export default function Form({ product, categoryOptions }: Props) {
    const isEdit = !!product?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        category_id: product?.categoryId ?? categoryOptions[0]?.id ?? '',
        name: product?.name ?? '',
        slug: product?.slug ?? '',
        description: product?.description ?? '',
        price: product?.price ?? 0,
        sale_price: product?.salePrice ?? '',
        image: null as File | null,
        remove_image: false,
        badge: product?.badge ?? '',
        weight: product?.weight ?? '',
        is_featured: product?.isFeatured ?? false,
        track_stock: product?.trackStock ?? true,
        allow_backorder: product?.allowBackorder ?? false,
        sizes: product?.sizes?.join(', ') ?? '',
        colors: product?.colors?.join(', ') ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { forceFormData: true as const, preserveScroll: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/products/${product!.id}`, options);
        } else {
            post('/admin/products', options);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Produk' : 'Tambah Produk'}
            breadcrumbs={[
                { label: 'Produk', href: '/admin/products' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Produk' : 'Tambah Produk'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Produk' : 'Tambah Produk'}
                backHref="/admin/products"
            />
            <Card><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="category_id">Kategori</Label>
                            <CategorySelect
                                value={data.category_id}
                                options={categoryOptions}
                                onChange={(value) => setData('category_id', value)}
                                required
                            />
                            <FieldError message={errors.category_id} /></div>
                        <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                    </div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="slug">Slug</Label><Input id="slug" value={data.slug} onChange={(e) => setData('slug', e.target.value)} /></div>
                        <div><Label htmlFor="badge">Badge</Label><Input id="badge" value={data.badge} onChange={(e) => setData('badge', e.target.value)} /></div>
                    </div>
                    <div><Label htmlFor="description">Deskripsi</Label><Textarea id="description" rows={4} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                    <div className="grid md:grid-cols-3 gap-4">
                        <div><Label htmlFor="price">Harga</Label><Input id="price" type="number" min={0} value={data.price} onChange={(e) => setData('price', Number(e.target.value))} required /></div>
                        <div><Label htmlFor="sale_price">Harga Sale</Label><Input id="sale_price" type="number" min={0} value={data.sale_price} onChange={(e) => setData('sale_price', e.target.value === '' ? '' : Number(e.target.value))} /></div>
                        <div><Label htmlFor="weight">Berat (gram)</Label><Input id="weight" type="number" min={0} value={data.weight} onChange={(e) => setData('weight', e.target.value === '' ? '' : Number(e.target.value))} /></div>
                    </div>
                    <div><Label htmlFor="image">Gambar Utama {!isEdit && '*'}</Label><Input id="image" type="file" accept="image/*" onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />
                        {isEdit && product?.imageUrl && <div className="mt-2 flex items-center gap-3"><img src={product.imageUrl} alt="" className="h-20 rounded" /><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_image} onChange={(e) => setData('remove_image', e.target.checked)} /> Hapus gambar</label></div>}
                        <FieldError message={errors.image} /></div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="sizes">Ukuran (comma-separated)</Label><Input id="sizes" value={data.sizes} onChange={(e) => setData('sizes', e.target.value)} placeholder="S, M, L, XL" /></div>
                        <div><Label htmlFor="colors">Warna (comma-separated)</Label><Input id="colors" value={data.colors} onChange={(e) => setData('colors', e.target.value)} placeholder="Merah, Biru" /></div>
                    </div>
                    <div className="flex flex-wrap gap-4">
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_featured} onChange={(e) => setData('is_featured', e.target.checked)} /> Featured</label>
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.track_stock} onChange={(e) => setData('track_stock', e.target.checked)} /> Track Stock</label>
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.allow_backorder} onChange={(e) => setData('allow_backorder', e.target.checked)} /> Allow Backorder</label>
                    </div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/products">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
