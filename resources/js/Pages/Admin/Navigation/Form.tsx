import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { LinkUrlField } from '@/components/admin/LinkUrlField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { Label } from '@/components/ui/label';

type NavItem = { id: number; menu: string; label: string; url: string; sortOrder?: number; isActive?: boolean; parentId?: number | null };
type ParentOption = { id: number; label: string };
type Props = { item?: NavItem; parents?: ParentOption[] };

export default function Form({ item, parents = [] }: Props) {
    const isEdit = !!item?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        menu: item?.menu ?? 'header',
        parent_id: item?.parentId ?? '',
        label: item?.label ?? '',
        url: item?.url ?? '',
        sort_order: item?.sortOrder ?? 0,
        is_active: item?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true };
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/navigation/${item!.id}`, options);
        } else {
            post('/admin/navigation', options);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Navigasi' : 'Tambah Navigasi'}
            breadcrumbs={[
                { label: 'Navigasi', href: '/admin/navigation' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Navigasi' : 'Tambah Navigasi'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Navigasi' : 'Tambah Navigasi'}
                    backHref="/admin/navigation"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/navigation">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="menu">Menu</Label>
                                <select id="menu" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.menu} onChange={(e) => setData('menu', e.target.value)}>
                                    <option value="header">Header</option>
                                    <option value="footer">Footer</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="parent_id">Parent (opsional)</Label>
                                <select id="parent_id" className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" value={data.parent_id} onChange={(e) => setData('parent_id', e.target.value)}>
                                    <option value="">— Tidak ada —</option>
                                    {parents.map((p) => <option key={p.id} value={p.id}>{p.label}</option>)}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="label">Label</Label>
                                <Input id="label" value={data.label} onChange={(e) => setData('label', e.target.value)} required />
                                <FieldError message={errors.label} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <LinkUrlField
                                    id="url"
                                    label="URL"
                                    value={data.url}
                                    onChange={(value) => setData('url', value)}
                                    placeholder="/page/tentang-kami"
                                    required
                                />
                                <FieldError message={errors.url} />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="sort_order">Urutan</Label>
                                <NumberInput id="sort_order" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', e)} />
                            </div>
                        </AdminFormGrid>
                        <AdminCheckboxRow
                            id="is_active"
                            label="Aktif"
                            checked={data.is_active}
                            onChange={(checked) => setData('is_active', checked)}
                        />
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
