import { cmsHtmlField } from '@/cms/fields/cmsHtmlField';

export type TwoColumnsProps = {
    leftHtml: string;
    rightHtml: string;
};

export function TwoColumnsBlock({ leftHtml, rightHtml }: TwoColumnsProps) {
    return (
        <div className="container mx-auto px-4 py-4 grid md:grid-cols-2 gap-8">
            <div className="prose prose-neutral max-w-none" dangerouslySetInnerHTML={{ __html: leftHtml }} />
            <div className="prose prose-neutral max-w-none" dangerouslySetInnerHTML={{ __html: rightHtml }} />
        </div>
    );
}

export const twoColumnsFields = {
    leftHtml: cmsHtmlField('Kolom Kiri', 480, 'Edit Kolom Kiri'),
    rightHtml: cmsHtmlField('Kolom Kanan', 480, 'Edit Kolom Kanan'),
};

export const twoColumnsDefaultProps: TwoColumnsProps = {
    leftHtml: '<p>Kolom kiri</p>',
    rightHtml: '<p>Kolom kanan</p>',
};
