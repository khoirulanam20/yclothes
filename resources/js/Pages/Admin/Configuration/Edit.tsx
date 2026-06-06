import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent, AdminFormCard } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    ConfigurationFieldRenderer,
    type ConfigField,
} from '@/components/admin/configuration/ConfigurationFieldRenderer';
import { CONFIGURATION_HREF, configurationBreadcrumbs } from '@/lib/configuration-nav';
import { buildConfigurationFieldGroups } from '@/lib/configuration-form-layout';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { PromoBarPreview } from '@/components/admin/PromoBarPreview';
import type { SharedPageProps } from '@/types';

type Section = {
    key: string;
    name: string;
    info?: string | null;
    type: 'form';
    fields: ConfigField[];
};

type Props = {
    section: Section;
};

function buildInitialData(fields: ConfigField[]): Record<string, unknown> {
    const data: Record<string, unknown> = {};
    for (const field of fields) {
        data[field.name] = field.value ?? (field.type === 'boolean' ? false : '');
        if (field.type === 'image') {
            data[field.name] = null;
            data[`remove_${field.name}`] = false;
        }
    }
    return data;
}

export default function Edit({ section }: Props) {
    const { theme } = usePage<SharedPageProps>().props;
    const initial = buildInitialData(section.fields) as Record<string, string | number | boolean | File | null>;
    const { data, setData, post, processing, errors } = useForm(initial);
    const [testEmail, setTestEmail] = useState('');

    const fieldGroups = useMemo(
        () => buildConfigurationFieldGroups(section.fields, section.key),
        [section.fields, section.key],
    );

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/admin/configuration/${section.key.replace(/\./g, '/')}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const saveFooter = (
        <>
            <Button type="button" variant="outline" asChild>
                <Link href={CONFIGURATION_HREF}>Batal</Link>
            </Button>
            <Button type="submit" disabled={processing}>
                {processing ? 'Menyimpan...' : 'Simpan Konfigurasi'}
            </Button>
        </>
    );

    return (
        <AdminLayout
            title={section.name}
            breadcrumbs={configurationBreadcrumbs({ label: section.name })}
        >
            <Head title={section.name} />

            <AdminContent>
                <AdminPageHeader
                    title={section.name}
                    description={section.info ?? undefined}
                    backHref={CONFIGURATION_HREF}
                />

                {section.key === 'general.header_offer' && (
                    <Card className="w-full">
                        <CardContent className="p-6 space-y-3">
                            <h3 className="font-medium">Preview Bar Promo</h3>
                            <p className="text-sm text-muted-foreground">
                                Pratinjau langsung strip promo di bagian atas situs.
                            </p>
                            <PromoBarPreview
                                enabled={Boolean(data.promo_bar_enabled)}
                                text={String(data.promo_bar_text ?? '')}
                                ctaLabel={String(data.promo_bar_cta_label ?? 'Hubungi WA')}
                                bgColor={String(data.promo_bar_bg_color ?? '')}
                                textColor={String(data.promo_bar_text_color ?? '')}
                                storeLocation={theme.storeLocation}
                                waNumber={theme.waNumber}
                            />
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={submit}>
                    <AdminFormCard
                        contentClassName="space-y-8"
                        footer={saveFooter}
                    >
                        {fieldGroups.map((group, index) => (
                            <section
                                key={group.title ?? `group-${index}`}
                                className={cn(index > 0 && 'border-t pt-8')}
                            >
                                {group.title && (
                                    <div className="mb-4">
                                        <h2 className="text-sm font-semibold">{group.title}</h2>
                                    </div>
                                )}
                                <div
                                    className={cn(
                                        group.layout === 'grid-2'
                                            ? 'grid gap-4 sm:grid-cols-2 xl:grid-cols-3'
                                            : 'space-y-5',
                                    )}
                                >
                                    {group.fields.map((field) => (
                                        <ConfigurationFieldRenderer
                                            key={field.name}
                                            field={field}
                                            data={data}
                                            errors={errors}
                                            compact={group.layout === 'grid-2'}
                                            setData={(key, value) =>
                                                setData(key as keyof typeof data, value as typeof data[keyof typeof data])
                                            }
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}
                    </AdminFormCard>
                </form>

                {section.key === 'general.email' && (
                    <Card className="w-full">
                        <CardContent className="p-6 space-y-3">
                            <h3 className="font-medium">Kirim Email Test</h3>
                            <p className="text-sm text-muted-foreground">
                                Simpan pengaturan SMTP terlebih dahulu, lalu kirim email percobaan.
                            </p>
                            <div className="flex gap-2 flex-wrap">
                                <Input
                                    type="email"
                                    placeholder="email@example.com"
                                    value={testEmail}
                                    onChange={(e) => setTestEmail(e.target.value)}
                                    className="max-w-xs"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.post('/admin/configuration/test-email', { email: testEmail }, { preserveScroll: true })}
                                    disabled={!testEmail}
                                >
                                    Kirim Test
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </AdminContent>
        </AdminLayout>
    );
}
