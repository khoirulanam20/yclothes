import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminHelpPanel } from '@/components/admin/AdminHelpPanel';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { productCreateHelp } from '@/lib/admin-help-content';

type Option = { id: number; name: string };
type TypeOption = { value: string; label: string };

type Props = {
    attributeFamilyOptions: Option[];
    productTypes: TypeOption[];
};

export default function Create({ attributeFamilyOptions, productTypes }: Props) {
    const defaultFamily = attributeFamilyOptions[0]?.id ?? '';
    const { data, setData, post, processing, errors } = useForm({
        type: productTypes[0]?.value ?? 'simple',
        attribute_family_id: defaultFamily,
        sku: '',
        name: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/products');
    };

    return (
        <AdminLayout
            title="Tambah Produk"
            breadcrumbs={[
                { label: 'Produk', href: '/admin/products' },
                { label: 'Tambah' },
            ]}
        >
            <Head title="Tambah Produk" />
            <AdminContent>
                <AdminPageHeader title="Tambah Produk" backHref="/admin/products" />

                <AdminHelpPanel section={productCreateHelp} defaultOpen />

                <form onSubmit={submit}>
                    <AdminFormCard
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/products">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Simpan & Lanjut Edit
                                </Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="type">Tipe Produk</Label>
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
                                <FieldError message={errors.type} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="attribute_family_id">Keluarga Atribut</Label>
                                <select
                                    id="attribute_family_id"
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    value={data.attribute_family_id}
                                    onChange={(e) => setData('attribute_family_id', Number(e.target.value))}
                                >
                                    {attributeFamilyOptions.map((f) => (
                                        <option key={f.id} value={f.id}>
                                            {f.name}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.attribute_family_id} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sku">SKU</Label>
                                <Input
                                    id="sku"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                    required
                                />
                                <FieldError message={errors.sku} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="name">Nama</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <FieldError message={errors.name} />
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
