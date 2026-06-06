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
    brandName?: string | null;
    brandLogo?: string | null;
    brandLogoUrl?: string | null;
    favicon?: string | null;
    faviconUrl?: string | null;
    colorGold?: string;
    colorAccent?: string;
    socialInstagram?: string;
    socialFacebook?: string;
    socialTiktok?: string;
};

export default function Edit(props: Props) {
    const { data, setData, post, processing, errors } = useForm({
        brand_name: props.brandName ?? '',
        brand_logo: null as File | null,
        remove_logo: false,
        favicon: null as File | null,
        remove_favicon: false,
        color_gold: props.colorGold ?? '#C2A56D',
        color_accent: props.colorAccent ?? '#547A95',
        social_instagram: props.socialInstagram ?? '',
        social_facebook: props.socialFacebook ?? '',
        social_tiktok: props.socialTiktok ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/theme', { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Tema & Branding" breadcrumbs={[{ label: 'Tema & Branding' }]}>
            <Head title="Tema & Branding" />
            <AdminPageHeader title="Tema & Branding" description="Logo, favicon, warna, dan sosial media." />
            <form onSubmit={submit} className="space-y-6 max-w-3xl">
                <Card>
                    <CardHeader><CardTitle>Brand</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div><Label htmlFor="brand_name">Nama Brand</Label><Input id="brand_name" value={data.brand_name} onChange={(e) => setData('brand_name', e.target.value)} /><FieldError message={errors.brand_name} /></div>
                        <div>
                            <Label htmlFor="brand_logo">Logo</Label>
                            <Input id="brand_logo" type="file" accept="image/*" onChange={(e) => setData('brand_logo', e.target.files?.[0] ?? null)} />
                            {props.brandLogoUrl && (
                                <div className="mt-2 flex items-center gap-3">
                                    <img src={props.brandLogoUrl} alt="" className="h-10" />
                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_logo} onChange={(e) => setData('remove_logo', e.target.checked)} /> Hapus logo</label>
                                </div>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="favicon">Favicon</Label>
                            <Input id="favicon" type="file" accept="image/*,.ico" onChange={(e) => setData('favicon', e.target.files?.[0] ?? null)} />
                            {props.faviconUrl && (
                                <div className="mt-2 flex items-center gap-3">
                                    <img src={props.faviconUrl} alt="" className="h-8 w-8" />
                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_favicon} onChange={(e) => setData('remove_favicon', e.target.checked)} /> Hapus favicon</label>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Warna</CardTitle></CardHeader>
                    <CardContent className="grid md:grid-cols-2 gap-4">
                        <div><Label htmlFor="color_gold">Warna Utama</Label><Input id="color_gold" type="color" value={data.color_gold} onChange={(e) => setData('color_gold', e.target.value)} /></div>
                        <div><Label htmlFor="color_accent">Warna Sekunder</Label><Input id="color_accent" type="color" value={data.color_accent} onChange={(e) => setData('color_accent', e.target.value)} /></div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Sosial Media</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div><Label htmlFor="social_instagram">Instagram</Label><Input id="social_instagram" value={data.social_instagram} onChange={(e) => setData('social_instagram', e.target.value)} /></div>
                        <div><Label htmlFor="social_facebook">Facebook</Label><Input id="social_facebook" value={data.social_facebook} onChange={(e) => setData('social_facebook', e.target.value)} /></div>
                        <div><Label htmlFor="social_tiktok">TikTok</Label><Input id="social_tiktok" value={data.social_tiktok} onChange={(e) => setData('social_tiktok', e.target.value)} /></div>
                    </CardContent>
                </Card>
                <Button type="submit" disabled={processing}>Simpan Tema</Button>
            </form>
        </AdminLayout>
    );
}
