import { Head } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import PageRenderer from '@/cms/PageRenderer';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import type { PuckData } from '@/cms/puckConfig';

type CmsPage = {
    title: string;
    slug: string;
    layoutJson?: PuckData | null;
    metaTitle?: string | null;
    metaDescription?: string | null;
};

type Props = { page: CmsPage };

export default function Show({ page }: Props) {
    const hasPuckLayout = page.layoutJson && page.layoutJson.content?.length;
    const pageTitle = page.metaTitle ?? page.title;

    return (
        <GuestLayout>
            <Head title={pageTitle}>
                {page.metaDescription && <meta name="description" content={page.metaDescription} />}
            </Head>

            {hasPuckLayout ? (
                <PageRenderer layoutJson={page.layoutJson} pageTitle={page.title} />
            ) : (
                <PageContainer narrow>
                    <Breadcrumb
                        items={[
                            { label: 'Beranda', href: '/' },
                            { label: page.title },
                        ]}
                    />
                    <SectionCard>
                        <h1 className="mb-2 text-2xl font-bold">{page.title}</h1>
                        <p className="text-muted-foreground">Halaman belum memiliki konten.</p>
                    </SectionCard>
                </PageContainer>
            )}
        </GuestLayout>
    );
}
