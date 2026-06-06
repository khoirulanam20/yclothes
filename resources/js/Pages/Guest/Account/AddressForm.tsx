import { Head, Link, useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { WilayahSelect, type WilayahValue } from '@/components/storefront/WilayahSelect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FieldError } from '@/components/admin/FieldError';

type Address = {
    id?: number; label?: string; recipientName?: string; phone?: string; streetAddress?: string;
    provinceCode?: string; provinceName?: string; regencyCode?: string; regencyName?: string;
    districtCode?: string; districtName?: string; villageCode?: string; villageName?: string;
    postalCode?: string;
};
type Props = { address: Address | null };

export default function AddressForm({ address }: Props) {
    const isEdit = !!address?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        label: address?.label ?? 'Rumah',
        recipient_name: address?.recipientName ?? '',
        phone: address?.phone ?? '',
        street_address: address?.streetAddress ?? '',
        province_code: address?.provinceCode ?? '',
        province_name: address?.provinceName ?? '',
        regency_code: address?.regencyCode ?? '',
        regency_name: address?.regencyName ?? '',
        district_code: address?.districtCode ?? '',
        district_name: address?.districtName ?? '',
        village_code: address?.villageCode ?? '',
        village_name: address?.villageName ?? '',
        postal_code: address?.postalCode ?? '',
        type: 'both',
        is_default: false,
    });

    const wilayah: WilayahValue = {
        provinceCode: data.province_code, provinceName: data.province_name,
        regencyCode: data.regency_code, regencyName: data.regency_name,
        districtCode: data.district_code, districtName: data.district_name,
        villageCode: data.village_code, villageName: data.village_name,
        postalCode: data.postal_code,
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) put(`/account/addresses/${address!.id}`);
        else post('/account/addresses');
    };

    return (
        <AccountLayout title={isEdit ? 'Edit Alamat' : 'Tambah Alamat'}>
            <Head title={isEdit ? 'Edit Alamat' : 'Tambah Alamat'} />
            <form onSubmit={submit}>
                <AccountPageShell
                    title={isEdit ? 'Edit Alamat Pengiriman' : 'Alamat Pengiriman Baru'}
                    description="Lengkapi detail alamat untuk checkout lebih cepat."
                >
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Label</Label>
                                <Input value={data.label} onChange={(e) => setData('label', e.target.value)} required />
                                <FieldError message={errors.label} />
                            </div>
                            <div>
                                <Label>Nama Penerima</Label>
                                <Input value={data.recipient_name} onChange={(e) => setData('recipient_name', e.target.value)} required />
                            </div>
                        </div>
                        <div>
                            <Label>Telepon</Label>
                            <Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} required />
                        </div>
                        <div>
                            <Label>Alamat Jalan</Label>
                            <Textarea rows={2} value={data.street_address} onChange={(e) => setData('street_address', e.target.value)} required />
                        </div>
                        <WilayahSelect
                            value={wilayah}
                            onChange={(w) => setData({
                                ...data,
                                province_code: w.provinceCode, province_name: w.provinceName,
                                regency_code: w.regencyCode, regency_name: w.regencyName,
                                district_code: w.districtCode, district_name: w.districtName,
                                village_code: w.villageCode, village_name: w.villageName,
                                postal_code: w.postalCode,
                            })}
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.is_default} onChange={(e) => setData('is_default', e.target.checked)} />
                            Jadikan alamat utama
                        </label>
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>{isEdit ? 'Simpan Alamat' : 'Tambah Alamat'}</Button>
                            <Button variant="outline" asChild><Link href="/account/addresses">Batal</Link></Button>
                        </div>
                    </div>
                </AccountPageShell>
            </form>
        </AccountLayout>
    );
}
