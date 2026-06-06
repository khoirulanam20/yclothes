import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminCheckboxRow, AdminContent, AdminFormGrid } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    siteTitle: string; siteDescription: string; heroTitle: string; heroSubtitle: string;
    heroImageUrl?: string | null; bannerTitle: string; bannerText: string;
    bannerButton: string; bannerLink: string; ctaText: string; ctaLink: string;
};

export default function Appearance(props: Props) {
    const { data, setData, post, processing } = useForm({
        site_title: props.siteTitle,
        site_description: props.siteDescription,
        hero_title: props.heroTitle,
        hero_subtitle: props.heroSubtitle,
        hero_image: null as File | null,
        remove_hero_image: false,
        banner_title: props.bannerTitle,
        banner_text: props.bannerText,
        banner_button: props.bannerButton,
        banner_link: props.bannerLink,
        cta_text: props.ctaText,
        cta_link: props.ctaLink,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/appearance', { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Tampilan Toko" breadcrumbs={[{ label: 'Tampilan Toko' }]}>
            <Head title="Tampilan Toko" />
            <AdminContent>
                <AdminPageHeader title="Tampilan Toko" />
                <form onSubmit={submit} className="space-y-6">
                    <Card className="w-full">
                        <CardHeader><CardTitle>SEO & Hero</CardTitle></CardHeader>
                        <CardContent>
                            <AdminFormGrid columns={2}>
                                <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                    <Label htmlFor="site_title">Site Title</Label>
                                    <Input id="site_title" value={data.site_title} onChange={(e) => setData('site_title', e.target.value)} />
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="site_description">Site Description</Label>
                                    <Textarea id="site_description" rows={2} value={data.site_description} onChange={(e) => setData('site_description', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="hero_title">Hero Title</Label>
                                    <Input id="hero_title" value={data.hero_title} onChange={(e) => setData('hero_title', e.target.value)} />
                                </div>
                                <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                    <Label htmlFor="hero_subtitle">Hero Subtitle</Label>
                                    <Textarea id="hero_subtitle" rows={2} value={data.hero_subtitle} onChange={(e) => setData('hero_subtitle', e.target.value)} />
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="hero_image">Hero Image</Label>
                                    <Input id="hero_image" type="file" accept="image/*" onChange={(e) => setData('hero_image', e.target.files?.[0] ?? null)} />
                                    {props.heroImageUrl && (
                                        <div className="mt-2 flex items-start gap-4">
                                            <img src={props.heroImageUrl} alt="Pratinjau hero" className="h-24 rounded border object-cover" />
                                            <AdminCheckboxRow
                                                id="remove_hero_image"
                                                label="Hapus gambar"
                                                checked={data.remove_hero_image}
                                                onChange={(checked) => setData('remove_hero_image', checked)}
                                            />
                                        </div>
                                    )}
                                </div>
                            </AdminFormGrid>
                        </CardContent>
                    </Card>
                    <Card className="w-full">
                        <CardHeader><CardTitle>Banner & CTA</CardTitle></CardHeader>
                        <CardContent>
                            <AdminFormGrid columns={2}>
                                <div className="space-y-2">
                                    <Label htmlFor="banner_title">Banner Title</Label>
                                    <Input id="banner_title" value={data.banner_title} onChange={(e) => setData('banner_title', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="banner_button">Banner Button</Label>
                                    <Input id="banner_button" value={data.banner_button} onChange={(e) => setData('banner_button', e.target.value)} />
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="banner_text">Banner Text</Label>
                                    <Textarea id="banner_text" rows={2} value={data.banner_text} onChange={(e) => setData('banner_text', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="banner_link">Banner Link</Label>
                                    <Input id="banner_link" value={data.banner_link} onChange={(e) => setData('banner_link', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="cta_text">CTA Text</Label>
                                    <Input id="cta_text" value={data.cta_text} onChange={(e) => setData('cta_text', e.target.value)} />
                                </div>
                                <div className="space-y-2 md:col-span-2 xl:col-span-1">
                                    <Label htmlFor="cta_link">CTA Link</Label>
                                    <Input id="cta_link" value={data.cta_link} onChange={(e) => setData('cta_link', e.target.value)} />
                                </div>
                            </AdminFormGrid>
                        </CardContent>
                    </Card>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </AdminContent>
        </AdminLayout>
    );
}
