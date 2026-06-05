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
            <AdminPageHeader
                title={isEdit ? 'Edit FAQ Item' : 'Tambah FAQ Item'}
                description={category.name}
                backHref={`/admin/faq-categories/${category.id}/items`}
            />
            <Card className="max-w-2xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="question">Pertanyaan</Label><Input id="question" value={data.question} onChange={(e) => setData('question', e.target.value)} required /><FieldError message={errors.question} /></div>
                    <div><Label htmlFor="answer">Jawaban</Label><Textarea id="answer" rows={6} value={data.answer} onChange={(e) => setData('answer', e.target.value)} required /><FieldError message={errors.answer} /></div>
                    <div><Label htmlFor="sort_order">Urutan</Label><Input id="sort_order" type="number" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', Number(e.target.value))} /></div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href={`/admin/faq-categories/${category.id}/items`}>Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
