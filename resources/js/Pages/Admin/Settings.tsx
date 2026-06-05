import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    user: { name: string; email: string };
    brandName: string;
    brandLogo?: string | null;
    waNumber: string;
    storeLocation: string;
    flashSaleEndsAt?: string;
    colorGold: string;
    colorAccent: string;
    socialInstagram?: string;
    socialFacebook?: string;
    socialTiktok?: string;
    promoBarText?: string;
};

export default function Settings(props: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: props.user.name,
        email: props.user.email,
        password: '',
        password_confirmation: '',
        brand_name: props.brandName,
        brand_logo: null as File | null,
        remove_logo: false,
        wa_number: props.waNumber,
        store_location: props.storeLocation,
        flash_sale_ends_at: props.flashSaleEndsAt?.slice(0, 16) ?? '',
        color_gold: props.colorGold,
        color_accent: props.colorAccent,
        social_instagram: props.socialInstagram ?? '',
        social_facebook: props.socialFacebook ?? '',
        social_tiktok: props.socialTiktok ?? '',
        promo_bar_text: props.promoBarText ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/settings', { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Pengaturan" breadcrumbs={[{ label: 'Pengaturan' }]}>
            <Head title="Pengaturan" />
            <AdminPageHeader title="Pengaturan" />
            <form onSubmit={submit} className="space-y-6">
                <div className="grid lg:grid-cols-2 gap-6">
                    <Card><CardHeader><CardTitle>Profil Admin</CardTitle></CardHeader><CardContent className="space-y-4">
                        <div><Label htmlFor="name">Nama</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required /><FieldError message={errors.name} /></div>
                        <div><Label htmlFor="email">Email</Label><Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required /><FieldError message={errors.email} /></div>
                        <div><Label htmlFor="password">Password Baru</Label><Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} /><FieldError message={errors.password} /></div>
                        <div><Label htmlFor="password_confirmation">Konfirmasi Password</Label><Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} /></div>
                    </CardContent></Card>
                    <Card><CardHeader><CardTitle>Toko</CardTitle></CardHeader><CardContent className="space-y-4">
                        <div><Label htmlFor="brand_name">Nama Brand</Label><Input id="brand_name" value={data.brand_name} onChange={(e) => setData('brand_name', e.target.value)} /></div>
                        <div><Label htmlFor="brand_logo">Logo</Label><Input id="brand_logo" type="file" accept="image/*" onChange={(e) => setData('brand_logo', e.target.files?.[0] ?? null)} />
                            {props.brandLogo && <div className="mt-2 flex items-center gap-3"><img src={`/storage/${props.brandLogo}`} alt="" className="h-10" /><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_logo} onChange={(e) => setData('remove_logo', e.target.checked)} /> Hapus logo</label></div>}</div>
                        <div><Label htmlFor="wa_number">WhatsApp</Label><Input id="wa_number" value={data.wa_number} onChange={(e) => setData('wa_number', e.target.value)} placeholder="6281234567890" /></div>
                        <div><Label htmlFor="store_location">Lokasi Toko</Label><Input id="store_location" value={data.store_location} onChange={(e) => setData('store_location', e.target.value)} /></div>
                        <div><Label htmlFor="flash_sale_ends_at">Flash Sale Berakhir</Label><Input id="flash_sale_ends_at" type="datetime-local" value={data.flash_sale_ends_at} onChange={(e) => setData('flash_sale_ends_at', e.target.value)} /></div>
                        <div><Label htmlFor="promo_bar_text">Teks Promo Bar</Label><Input id="promo_bar_text" value={data.promo_bar_text} onChange={(e) => setData('promo_bar_text', e.target.value)} placeholder="Free Ongkir Pembelian > Rp 200rb" /></div>
                    </CardContent></Card>
                </div>
                <Card><CardHeader><CardTitle>Tema Warna & Sosial Media</CardTitle></CardHeader><CardContent className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div><Label htmlFor="color_gold">Warna Utama</Label><Input id="color_gold" type="color" value={data.color_gold} onChange={(e) => setData('color_gold', e.target.value)} /><p className="text-xs text-muted-foreground mt-1">Tombol, harga, promo bar</p></div>
                    <div><Label htmlFor="color_accent">Warna Sekunder</Label><Input id="color_accent" type="color" value={data.color_accent} onChange={(e) => setData('color_accent', e.target.value)} /><p className="text-xs text-muted-foreground mt-1">Highlight & link sekunder</p></div>
                    <div><Label htmlFor="social_instagram">Instagram</Label><Input id="social_instagram" value={data.social_instagram} onChange={(e) => setData('social_instagram', e.target.value)} /></div>
                    <div><Label htmlFor="social_facebook">Facebook</Label><Input id="social_facebook" value={data.social_facebook} onChange={(e) => setData('social_facebook', e.target.value)} /></div>
                    <div><Label htmlFor="social_tiktok">TikTok</Label><Input id="social_tiktok" value={data.social_tiktok} onChange={(e) => setData('social_tiktok', e.target.value)} /></div>
                </CardContent></Card>
                <Button type="submit" disabled={processing}>Simpan Pengaturan</Button>
            </form>
        </AdminLayout>
    );
}
