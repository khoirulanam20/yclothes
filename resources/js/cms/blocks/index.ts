import type { CmsBlockDefinition } from '@/cms/registry';
import {
    ButtonBlock,
    buttonDefaultProps,
    buttonFields,
} from '@/cms/blocks/Button';
import {
    HeadingBlock,
    headingDefaultProps,
    headingFields,
} from '@/cms/blocks/Heading';
import {
    ImageBlock,
    imageDefaultProps,
    imageFields,
} from '@/cms/blocks/Image';
import {
    PageBannerBlock,
    pageBannerDefaultProps,
    pageBannerFields,
} from '@/cms/blocks/PageBanner';
import {
    RichTextBlock,
    richTextDefaultProps,
    richTextFields,
} from '@/cms/blocks/RichText';
import {
    SpacerBlock,
    spacerDefaultProps,
    spacerFields,
} from '@/cms/blocks/Spacer';
import {
    TwoColumnsBlock,
    twoColumnsDefaultProps,
    twoColumnsFields,
} from '@/cms/blocks/TwoColumns';

export const cmsBlocks: CmsBlockDefinition[] = [
    {
        type: 'PageBanner',
        label: 'Page Banner',
        description: 'Banner hero dengan judul dan gambar',
        category: 'layout',
        fields: pageBannerFields,
        defaultProps: pageBannerDefaultProps,
        render: PageBannerBlock,
    },
    {
        type: 'Spacer',
        label: 'Spacer',
        description: 'Jarak vertikal antar blok',
        category: 'layout',
        fields: spacerFields,
        defaultProps: spacerDefaultProps,
        render: SpacerBlock,
    },
    {
        type: 'TwoColumns',
        label: 'Two Columns',
        description: 'Dua kolom konten HTML',
        category: 'layout',
        fields: twoColumnsFields,
        defaultProps: twoColumnsDefaultProps,
        render: TwoColumnsBlock,
    },
    {
        type: 'Heading',
        label: 'Heading',
        description: 'Judul H1, H2, atau H3',
        category: 'content',
        fields: headingFields,
        defaultProps: headingDefaultProps,
        render: HeadingBlock,
    },
    {
        type: 'RichText',
        label: 'Rich Text',
        description: 'Konten HTML / teks bebas',
        category: 'content',
        fields: richTextFields,
        defaultProps: richTextDefaultProps,
        render: RichTextBlock,
    },
    {
        type: 'Image',
        label: 'Image',
        description: 'Gambar dengan caption opsional',
        category: 'content',
        fields: imageFields,
        defaultProps: imageDefaultProps,
        render: ImageBlock,
    },
    {
        type: 'Button',
        label: 'Button',
        description: 'Tombol link ke URL',
        category: 'content',
        fields: buttonFields,
        defaultProps: buttonDefaultProps,
        render: ButtonBlock,
    },
];
