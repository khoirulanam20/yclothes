import { storageUrl } from '@/cms/storageUrl';
import { cmsImageField } from '@/cms/fields/cmsImageField';

export type ImageProps = {
    src: string;
    alt: string;
    caption?: string;
};

export function ImageBlock({ src, alt, caption }: ImageProps) {
    const url = storageUrl(src);

    if (!url) {
        return (
            <figure className="container mx-auto px-4 py-4">
                <div className="flex h-48 w-full items-center justify-center rounded-lg border border-dashed bg-muted text-sm text-muted-foreground">
                    Unggah gambar
                </div>
            </figure>
        );
    }

    return (
        <figure className="container mx-auto px-4 py-4">
            <img src={url} alt={alt} className="max-h-[480px] w-full rounded-lg object-cover" />
            {caption && (
                <figcaption className="mt-2 text-center text-sm text-muted-foreground">{caption}</figcaption>
            )}
        </figure>
    );
}

export const imageFields = {
    src: cmsImageField('Gambar'),
    alt: { type: 'text' as const, label: 'Alt Text' },
    caption: { type: 'text' as const, label: 'Caption' },
};

export const imageDefaultProps: ImageProps = {
    src: '',
    alt: '',
    caption: '',
};
