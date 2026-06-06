import type { ReactNode } from 'react';
import { Breadcrumb } from '@/components/storefront/Breadcrumb';
import { PageContainer } from '@/components/storefront/PageContainer';

type RootProps = {
    showBreadcrumb?: string | boolean;
    pageTitle?: string;
    children?: ReactNode;
};

function shouldShowBreadcrumb(value: string | boolean | undefined): boolean {
    return value !== 'no' && value !== false;
}

export function CmsPageRoot({ children, showBreadcrumb, pageTitle }: RootProps) {
    return (
        <div className="cms-page pb-8">
            {shouldShowBreadcrumb(showBreadcrumb) && pageTitle ? (
                <PageContainer>
                    <Breadcrumb
                        items={[
                            { label: 'Beranda', href: '/' },
                            { label: pageTitle },
                        ]}
                    />
                </PageContainer>
            ) : null}
            {children}
        </div>
    );
}
