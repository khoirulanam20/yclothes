import { cmsHtmlField } from '@/cms/fields/cmsHtmlField';
import { CmsHtmlContent } from '@/cms/CmsHtmlContent';

export type RichTextProps = {
    html: string;
};

export function RichTextBlock({ html }: RichTextProps) {
    return <CmsHtmlContent html={html} />;
}

export const richTextFields = {
    html: cmsHtmlField('Konten'),
};

export const richTextDefaultProps: RichTextProps = {
    html: '<p>Konten halaman...</p>',
};
