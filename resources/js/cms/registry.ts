import type { Config, DefaultComponentProps, Fields } from '@measured/puck';
import type { ComponentType } from 'react';
import { createElement } from 'react';
import { CmsPageRoot } from '@/cms/puckRoot';

export type CmsBlockCategory = 'layout' | 'content';

export type CmsBlockDefinition = {
    type: string;
    label: string;
    description?: string;
    category: CmsBlockCategory;
    fields: Fields;
    defaultProps: DefaultComponentProps;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    render: ComponentType<any>;
};

const CATEGORY_LABELS: Record<CmsBlockCategory, string> = {
    layout: 'Layout',
    content: 'Konten',
};

export function buildPuckConfig(blocks: CmsBlockDefinition[]): Config {
    const components: Config['components'] = {};

    for (const block of blocks) {
        components[block.type] = {
            label: block.label,
            fields: block.fields,
            defaultProps: block.defaultProps,
            render: (props) => createElement(block.render, props),
        };
    }

    const categories: Config['categories'] = {
        layout: {
            title: CATEGORY_LABELS.layout,
            components: blocks.filter((b) => b.category === 'layout').map((b) => b.type),
        },
        content: {
            title: CATEGORY_LABELS.content,
            components: blocks.filter((b) => b.category === 'content').map((b) => b.type),
        },
    };

    return {
        root: {
            fields: {
                showBreadcrumb: {
                    type: 'select',
                    label: 'Tampilkan Breadcrumb',
                    options: [
                        { label: 'Ya', value: 'yes' },
                        { label: 'Tidak', value: 'no' },
                    ],
                },
                pageTitle: { type: 'text', label: 'Judul Halaman (breadcrumb)' },
            },
            defaultProps: {
                showBreadcrumb: 'yes',
                pageTitle: '',
            },
            render: (props: Record<string, unknown>) => createElement(CmsPageRoot, props),
        },
        categories,
        components,
    };
}

export const CMS_BLOCK_TYPES = [
    'PageBanner',
    'Spacer',
    'TwoColumns',
    'Heading',
    'RichText',
    'Image',
    'Button',
] as const;

export type CmsBlockType = (typeof CMS_BLOCK_TYPES)[number];
