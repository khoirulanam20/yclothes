import { storageUrl } from '@/cms/storageUrl';
import { cmsImageField } from '@/cms/fields/cmsImageField';

export type PageBannerProps = {
    title: string;
    subtitle?: string;
    imageUrl?: string;
};

export function PageBannerBlock({ title, subtitle, imageUrl }: PageBannerProps) {
    const imageSrc = storageUrl(imageUrl);
    const hasImage = Boolean(imageSrc);

    return (
        <section
            className={
                hasImage
                    ? 'relative px-4 py-16 text-center text-white bg-cover bg-center'
                    : 'relative bg-muted px-4 py-16 text-center text-foreground'
            }
            style={hasImage ? { backgroundImage: `url(${imageSrc})` } : undefined}
        >
            {hasImage && <div className="absolute inset-0 bg-black/50" />}
            <div className="relative container mx-auto">
                <h1 className="font-serif text-3xl font-bold md:text-4xl">{title}</h1>
                {subtitle && (
                    <p className={`mt-3 text-lg ${hasImage ? 'opacity-90' : 'text-muted-foreground'}`}>
                        {subtitle}
                    </p>
                )}
            </div>
        </section>
    );
}

export const pageBannerFields = {
    title: { type: 'text' as const, label: 'Judul' },
    subtitle: { type: 'text' as const, label: 'Subjudul' },
    imageUrl: cmsImageField('Gambar Banner'),
};

export const pageBannerDefaultProps: PageBannerProps = {
    title: 'Judul Halaman',
    subtitle: '',
    imageUrl: '',
};
