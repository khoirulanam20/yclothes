import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

type Bank = { id: number; bankName: string; accountNumber: string; accountName: string; isActive?: boolean };
type Props = { bank?: Bank };

export default function Form({ bank }: Props) {
    const isEdit = !!bank?.id;
    const { data, setData, post, transform, processing, errors } = useForm({
        bank_name: bank?.bankName ?? '',
        account_number: bank?.accountNumber ?? '',
        account_name: bank?.accountName ?? '',
        is_active: bank?.isActive ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            transform((d) => ({ ...d, _method: 'put' }));
            post(`/admin/payment-banks/${bank!.id}`);
        } else {
            post('/admin/payment-banks');
        }
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Rekening' : 'Tambah Rekening'}
            breadcrumbs={[
                { label: 'Rekening', href: '/admin/payment-banks' },
                { label: isEdit ? 'Edit' : 'Tambah' },
            ]}
        >
            <Head title={isEdit ? 'Edit Rekening' : 'Tambah Rekening'} />
            <AdminPageHeader
                title={isEdit ? 'Edit Rekening' : 'Tambah Rekening'}
                backHref="/admin/payment-banks"
            />
            <Card className="max-w-xl"><CardContent className="p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div><Label htmlFor="bank_name">Nama Bank</Label><Input id="bank_name" value={data.bank_name} onChange={(e) => setData('bank_name', e.target.value)} required /><FieldError message={errors.bank_name} /></div>
                    <div><Label htmlFor="account_number">No. Rekening</Label><Input id="account_number" value={data.account_number} onChange={(e) => setData('account_number', e.target.value)} required /></div>
                    <div><Label htmlFor="account_name">Atas Nama</Label><Input id="account_name" value={data.account_name} onChange={(e) => setData('account_name', e.target.value)} required /></div>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Aktif</label>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Simpan</Button><Button variant="outline" asChild><Link href="/admin/payment-banks">Batal</Link></Button></div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
