import { Head, usePage } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { HomeSectionRenderer, type HomeSection } from '@/components/storefront/HomeSectionRenderer';

type Props = {
    sections: HomeSection[];
};

export default function Home({ sections }: Props) {
    const { theme } = usePage().props;

    return (
        <GuestLayout>
            <Head title={theme.siteTitle ? `${theme.siteTitle}` : 'Beranda'}>
                {theme.siteDescription && (
                    <meta head-key="description" name="description" content={theme.siteDescription} />
                )}
            </Head>
            {sections.map((section) => (
                <HomeSectionRenderer key={section.id} section={section} />
            ))}
        </GuestLayout>
    );
}
