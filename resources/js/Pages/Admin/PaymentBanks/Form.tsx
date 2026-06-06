import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

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
            breadcrumbs={configurationSectionBreadcrumbs('Rekening Transfer', '/admin/payment-banks', {
                label: isEdit ? 'Edit' : 'Tambah',
            })}
        >
            <Head title={isEdit ? 'Edit Rekening' : 'Tambah Rekening'} />
            <AdminContent>
                <AdminPageHeader
                    title={isEdit ? 'Edit Rekening' : 'Tambah Rekening'}
                    backHref="/admin/payment-banks"
                />
                <form onSubmit={submit}>
                    <AdminFormCard
                        footer={(
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/payment-banks">Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>Simpan</Button>
                            </>
                        )}
                    >
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="bank_name">Nama Bank</Label>
                                <Input id="bank_name" value={data.bank_name} onChange={(e) => setData('bank_name', e.target.value)} required />
                                <FieldError message={errors.bank_name} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="account_number">No. Rekening</Label>
                                <Input id="account_number" value={data.account_number} onChange={(e) => setData('account_number', e.target.value)} required />
                            </div>
                            <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                <Label htmlFor="account_name">Atas Nama</Label>
                                <Input id="account_name" value={data.account_name} onChange={(e) => setData('account_name', e.target.value)} required />
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
