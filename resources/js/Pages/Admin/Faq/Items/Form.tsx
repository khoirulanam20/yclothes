import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type FaqItem = { id: number; question: string; answer: string; sortOrder?: number; isActive?: boolean };
type FaqCategory = { id: number; name: string };
type Props = { category: FaqCategory; item?: FaqItem };

export default function Form({ category, item }: Props) {
    const isEdit = !!item?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        question: item?.question ?? '',
        answer: item?.answer ?? '',
        sort_order: item?.sortOrder ?? 0,
        is_active: item?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const base = `/admin/faq-categories/${category.id}/items`;
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`${base}/${item!.id}`);
        } else {
            post(base);
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit FAQ Item' : 'Tambah FAQ Item'}
            breadcrumbs={[
                { label: 'FAQ', href: '/admin/faq-categories' },
                { label: category.name, href: `/admin/faq-categories/${category.id}/items` },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit FAQ Item' : 'Tambah FAQ Item'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit FAQ Item' : 'Tambah FAQ Item'}
                    description={category.name}
                    backHref={`/admin/faq-categories/${category.id}/items`}
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-5"
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={`/admin/faq-categories/${category.id}/items`}>Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="question">Pertanyaan</Label>
                                <Input id="question" value={data.question} onChange={(e) => setData('question', e.target.value)} required />
                                <FieldError message={errors.question} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="answer">Jawaban</Label>
                                <Textarea id="answer" rows={6} value={data.answer} onChange={(e) => setData('answer', e.target.value)} required />
                                <FieldError message={errors.answer} />
                            </div>
                            <div className="space-y-2">
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
