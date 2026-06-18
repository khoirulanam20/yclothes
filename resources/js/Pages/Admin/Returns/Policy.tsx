import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CONFIGURATION_HREF, configurationSectionBreadcrumbs } from '@/lib/configuration-nav';

type Props = {
    policy: {
        defaultReturnWindowDays: number;
        defaultWarrantyDays: number;
        returnReasons: string[];
        policyText?: string | null;
    };
};

export default function Policy({ policy }: Props) {
    const { data, setData, processing } = useForm({
        default_return_window_days: policy.defaultReturnWindowDays,
        default_warranty_days: policy.defaultWarrantyDays,
        return_reasons: policy.returnReasons.join('\n'),
        policy_text: policy.policyText ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/admin/returns/policy', {
            default_return_window_days: data.default_return_window_days,
            default_warranty_days: data.default_warranty_days,
            return_reasons: data.return_reasons.split('\n').map((s) => s.trim()).filter(Boolean),
            policy_text: data.policy_text,
        });
    };

    return (
        <AdminLayout title="Kebijakan Retur" breadcrumbs={configurationSectionBreadcrumbs('Kebijakan Retur')}>
            <Head title="Kebijakan Retur" />
            <AdminContent>
                <AdminPageHeader title="Kebijakan Retur" backHref={CONFIGURATION_HREF} />
                <form onSubmit={submit} data-tour="returns-policy">
                    <AdminFormCard
                        footer={<Button type="submit" disabled={processing}>Simpan</Button>}
                    >
                        <h2 className="text-sm font-semibold mb-4">Pengaturan Global</h2>
                        <AdminFormGrid columns={2}>
                            <div className="space-y-2">
                                <Label htmlFor="default_return_window_days">Periode Retur (hari)</Label>
                                <NumberInput
                                    id="default_return_window_days"
                                    value={data.default_return_window_days}
                                    onChange={(e) => setData('default_return_window_days', e)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="default_warranty_days">Garansi Default (hari)</Label>
                                <NumberInput
                                    id="default_warranty_days"
                                    value={data.default_warranty_days}
                                    onChange={(e) => setData('default_warranty_days', e)}
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="return_reasons">Alasan Retur (satu per baris)</Label>
                                <Textarea id="return_reasons" rows={6} value={data.return_reasons} onChange={(e) => setData('return_reasons', e.target.value)} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="policy_text">Teks Kebijakan</Label>
                                <Textarea id="policy_text" rows={4} value={data.policy_text} onChange={(e) => setData('policy_text', e.target.value)} />
                            </div>
                        </AdminFormGrid>
                    </AdminFormCard>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
