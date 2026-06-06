import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    promoBarEnabled: boolean;
    storeLocation: string;
    promoBarText: string;
    promoBarCtaLabel: string;
    waNumber: string;
    promoBarBgColor: string;
    promoBarTextColor: string;
    themeColorGold: string;
};

export default function Edit(props: Props) {
    const { data, setData, post, processing } = useForm({
        promo_bar_enabled: props.promoBarEnabled,
        store_location: props.storeLocation,
        promo_bar_text: props.promoBarText,
        promo_bar_cta_label: props.promoBarCtaLabel,
        wa_number: props.waNumber,
        promo_bar_bg_color: props.promoBarBgColor,
        promo_bar_text_color: props.promoBarTextColor,
    });

    const previewBg = data.promo_bar_bg_color || props.themeColorGold;
    const previewFg = data.promo_bar_text_color || '#ffffff';

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/promo-bar', { preserveScroll: true });
    };

    return (
        <AdminLayout title="Bar Promo" breadcrumbs={[{ label: 'Bar Promo' }]}>
            <Head title="Bar Promo" />
            <AdminPageHeader title="Bar Promo" description="Atur strip promo di bagian atas situs." />
            <form onSubmit={submit} className="space-y-6 max-w-3xl">
                <div className="rounded-md text-xs py-2 px-4" style={{ backgroundColor: previewBg, color: previewFg }}>
                    <div className="container mx-auto flex justify-between items-center gap-2">
                        <span className="truncate">{data.store_location || 'Lokasi'}</span>
                        <span className="hidden sm:inline truncate">{data.promo_bar_text || 'Teks promo'}</span>
                        <span className="shrink-0 font-medium">{data.promo_bar_cta_label || 'Hubungi WA'}</span>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Pengaturan</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.promo_bar_enabled} onChange={(e) => setData('promo_bar_enabled', e.target.checked)} />
                            Tampilkan bar promo
                        </label>
                        <div><Label htmlFor="store_location">Teks Kiri (Lokasi)</Label><Input id="store_location" value={data.store_location} onChange={(e) => setData('store_location', e.target.value)} /></div>
                        <div><Label htmlFor="promo_bar_text">Teks Tengah (Promo)</Label><Input id="promo_bar_text" value={data.promo_bar_text} onChange={(e) => setData('promo_bar_text', e.target.value)} /></div>
                        <div className="grid md:grid-cols-2 gap-4">
                            <div><Label htmlFor="promo_bar_cta_label">Label Tombol Kanan</Label><Input id="promo_bar_cta_label" value={data.promo_bar_cta_label} onChange={(e) => setData('promo_bar_cta_label', e.target.value)} /></div>
                            <div><Label htmlFor="wa_number">Nomor WhatsApp</Label><Input id="wa_number" value={data.wa_number} onChange={(e) => setData('wa_number', e.target.value)} placeholder="6281234567890" /></div>
                        </div>
                        <div className="grid md:grid-cols-2 gap-4">
                            <div><Label htmlFor="promo_bar_bg_color">Warna Background</Label><Input id="promo_bar_bg_color" type="color" value={data.promo_bar_bg_color || props.themeColorGold} onChange={(e) => setData('promo_bar_bg_color', e.target.value)} /><p className="text-xs text-muted-foreground mt-1">Kosongkan dengan reset ke warna tema</p></div>
                            <div><Label htmlFor="promo_bar_text_color">Warna Teks</Label><Input id="promo_bar_text_color" type="color" value={data.promo_bar_text_color || '#ffffff'} onChange={(e) => setData('promo_bar_text_color', e.target.value)} /></div>
                        </div>
                    </CardContent>
                </Card>
                <Button type="submit" disabled={processing}>Simpan</Button>
            </form>
        </AdminLayout>
    );
}
