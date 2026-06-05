import { buildPuckConfig } from '@/cms/registry';
import { cmsBlocks } from '@/cms/blocks';

export type PuckData = {
    content: { type: string; props: Record<string, unknown> }[];
    root?: { props?: Record<string, unknown> };
    zones?: Record<string, { type: string; props: Record<string, unknown> }[]>;
};

export const puckConfig = buildPuckConfig(cmsBlocks);

export const emptyPuckData: PuckData = {
    content: [],
    root: { props: { showBreadcrumb: 'yes', pageTitle: '' } },
};
