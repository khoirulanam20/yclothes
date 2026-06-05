import { Head, useForm } from '@inertiajs/react';
import { useCallback, useMemo, useRef, useState } from 'react';
import { Puck } from '@measured/puck';
import '@measured/puck/puck.css';
import '../../../../css/cms-builder.css';
import CmsBuilderLayout from '@/Layouts/CmsBuilderLayout';
import { CmsPageSettingsDialog } from '@/components/admin/CmsPageSettingsDialog';
import { CmsBuilderToolbar } from '@/components/admin/cms/CmsBuilderToolbar';
import { CmsComponentDrawer } from '@/components/admin/cms/CmsComponentDrawer';
import { cmsBlocks } from '@/cms/blocks';
import { puckConfig, type PuckData, emptyPuckData } from '@/cms/puckConfig';

type CmsPage = {
    id: number;
    title: string;
    slug: string;
    layoutJson?: PuckData | null;
    metaTitle?: string | null;
    metaDescription?: string | null;
    status: string;
};

type Props = {
    page: CmsPage | null;
};

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function syncPageTitleInLayout(layoutJson: string, title: string): string {
    const layout = JSON.parse(layoutJson) as PuckData;

    return JSON.stringify({
        ...layout,
        root: {
            ...layout.root,
            props: {
                ...layout.root?.props,
                pageTitle: title,
            },
        },
    });
}

export default function Builder({ page }: Props) {
    const isNew = !page?.id;
    const [settingsOpen, setSettingsOpen] = useState(isNew);
    const slugManuallyEdited = useRef(Boolean(page?.slug));

    const initialLayout: PuckData = useMemo(() => {
        if (page?.layoutJson?.content?.length) {
            return page.layoutJson;
        }

        return {
            ...emptyPuckData,
            root: {
                props: {
                    showBreadcrumb: 'yes',
                    pageTitle: page?.title ?? '',
                },
            },
        };
    }, [page]);

    const { data, setData, post, put, processing, errors, transform } = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        status: page?.status ?? 'draft',
        meta_title: page?.metaTitle ?? '',
        meta_description: page?.metaDescription ?? '',
        layout_json: JSON.stringify(initialLayout),
    });

    const puckData = JSON.parse(data.layout_json) as PuckData;

    const updateLayout = useCallback(
        (newData: PuckData) => {
            const withTitle = {
                ...newData,
                root: {
                    ...newData.root,
                    props: {
                        ...newData.root?.props,
                        pageTitle: data.title,
                        showBreadcrumb: newData.root?.props?.showBreadcrumb ?? 'yes',
                    },
                },
            };
            setData('layout_json', JSON.stringify(withTitle));
        },
        [data.title, setData],
    );

    const handleSettingsChange = (field: keyof typeof data, value: string) => {
        if (field === 'slug') {
            slugManuallyEdited.current = true;
        }

        if (field === 'title') {
            setData({
                title: value,
                slug: isNew && !slugManuallyEdited.current ? slugify(value) : data.slug,
                layout_json: syncPageTitleInLayout(data.layout_json, value),
            });

            return;
        }

        setData(field, value);
    };

    const save = useCallback(() => {
        transform((formData) => ({
            ...formData,
            slug: formData.slug || slugify(formData.title),
            meta_title: formData.meta_title || formData.title,
        }));

        if (isNew) {
            post('/admin/cms-pages/builder', {
                preserveScroll: true,
                onSuccess: () => setSettingsOpen(false),
            });
        } else {
            put(`/admin/cms-pages/${page!.id}/builder`, {
                preserveScroll: true,
                onSuccess: () => setSettingsOpen(false),
            });
        }
    }, [isNew, page, post, put, transform]);

    const puckOverrides = useMemo(
        () => ({
            drawer: ({ children }: { children: React.ReactNode }) => (
                <CmsComponentDrawer blocks={cmsBlocks}>{children}</CmsComponentDrawer>
            ),
            header: () => (
                <CmsBuilderToolbar
                    title={data.title || page?.title || 'Halaman Baru'}
                    status={data.status}
                    backHref="/admin/cms-pages"
                    onOpenSettings={() => setSettingsOpen(true)}
                    onSave={save}
                    previewHref={!isNew ? `/admin/cms-pages/${page!.id}/preview` : undefined}
                    saving={processing}
                />
            ),
            headerActions: () => <></>,
        }),
        [data.title, data.status, isNew, page, processing, save],
    );

    return (
        <CmsBuilderLayout
            title={isNew ? 'Halaman Baru' : `Builder — ${page!.title}`}
            breadcrumbs={[
                { label: 'Halaman', href: '/admin/cms-pages' },
                { label: isNew ? 'Baru' : page!.title },
            ]}
        >
            <Head title={isNew ? 'Halaman Baru' : `Builder — ${page!.title}`} />

            <div className="cms-builder">
                <Puck
                    config={puckConfig}
                    data={puckData}
                    onChange={updateLayout}
                    onPublish={save}
                    headerTitle={data.title || 'Halaman Baru'}
                    overrides={puckOverrides}
                />
            </div>

            <CmsPageSettingsDialog
                open={settingsOpen}
                onOpenChange={setSettingsOpen}
                data={{
                    title: data.title,
                    slug: data.slug,
                    status: data.status,
                    meta_title: data.meta_title,
                    meta_description: data.meta_description,
                }}
                errors={errors}
                isNew={isNew}
                onChange={handleSettingsChange}
                onSave={save}
                processing={processing}
            />
        </CmsBuilderLayout>
    );
}
