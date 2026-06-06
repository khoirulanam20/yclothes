import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { FieldError } from '@/components/admin/FieldError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    siteTitle?: string | null;
    siteDescription?: string | null;
    siteKeywords?: string | null;
    ogImage?: string | null;
    ogImageUrl?: string | null;
    metaPixelId?: string | null;
    googleTagManagerId?: string | null;
    customHeadScripts?: string | null;
    customBodyScripts?: string | null;
};

export default function Edit(props: Props) {
    const { data, setData, post, processing, errors } = useForm({
        site_title: props.siteTitle ?? '',
        site_description: props.siteDescription ?? '',
        site_keywords: props.siteKeywords ?? '',
        og_image: null as File | null,
        remove_og_image: false,
        meta_pixel_id: props.metaPixelId ?? '',
        google_tag_manager_id: props.googleTagManagerId ?? '',
        custom_head_scripts: props.customHeadScripts ?? '',
        custom_body_scripts: props.customBodyScripts ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/integrations', { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="SEO & Integrasi" breadcrumbs={[{ label: 'SEO & Integrasi' }]}>
            <Head title="SEO & Integrasi" />
            <AdminPageHeader title="SEO & Integrasi" description="Meta tags global, Meta Pixel, GTM, dan custom scripts." />
            <form onSubmit={submit} className="space-y-6 max-w-3xl">
                <Card>
                    <CardHeader><CardTitle>SEO Global</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div><Label htmlFor="site_title">Site Title</Label><Input id="site_title" value={data.site_title} onChange={(e) => setData('site_title', e.target.value)} /><FieldError message={errors.site_title} /></div>
                        <div><Label htmlFor="site_description">Meta Description</Label><Textarea id="site_description" rows={3} value={data.site_description} onChange={(e) => setData('site_description', e.target.value)} /></div>
                        <div><Label htmlFor="site_keywords">Meta Keywords</Label><Input id="site_keywords" value={data.site_keywords} onChange={(e) => setData('site_keywords', e.target.value)} /></div>
                        <div>
                            <Label htmlFor="og_image">OG Image</Label>
                            <Input id="og_image" type="file" accept="image/*" onChange={(e) => setData('og_image', e.target.files?.[0] ?? null)} />
                            {props.ogImageUrl && (
                                <div className="mt-2 flex items-center gap-3">
                                    <img src={props.ogImageUrl} alt="" className="h-16 rounded" />
                                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remove_og_image} onChange={(e) => setData('remove_og_image', e.target.checked)} /> Hapus gambar</label>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Tracking</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <div><Label htmlFor="meta_pixel_id">Meta Pixel ID</Label><Input id="meta_pixel_id" value={data.meta_pixel_id} onChange={(e) => setData('meta_pixel_id', e.target.value)} placeholder="1234567890" /><FieldError message={errors.meta_pixel_id} /></div>
                        <div><Label htmlFor="google_tag_manager_id">Google Tag Manager ID</Label><Input id="google_tag_manager_id" value={data.google_tag_manager_id} onChange={(e) => setData('google_tag_manager_id', e.target.value)} placeholder="GTM-XXXXXXX" /><FieldError message={errors.google_tag_manager_id} /></div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Custom Scripts</CardTitle></CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-xs text-muted-foreground">Hanya admin tepercaya. Script akan di-inject langsung ke HTML.</p>
                        <div><Label htmlFor="custom_head_scripts">Head Scripts</Label><Textarea id="custom_head_scripts" rows={5} className="font-mono text-xs" value={data.custom_head_scripts} onChange={(e) => setData('custom_head_scripts', e.target.value)} /></div>
                        <div><Label htmlFor="custom_body_scripts">Body Scripts (sebelum &lt;/body&gt;)</Label><Textarea id="custom_body_scripts" rows={5} className="font-mono text-xs" value={data.custom_body_scripts} onChange={(e) => setData('custom_body_scripts', e.target.value)} /></div>
                    </CardContent>
                </Card>
                <Button type="submit" disabled={processing}>Simpan</Button>
            </form>
        </AdminLayout>
    );
}
