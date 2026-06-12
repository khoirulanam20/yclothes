import { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import { AdminSidebar } from '@/components/admin/AdminSidebar';
import { AdminTourProvider } from '@/components/admin/AdminTourProvider';
import { AdminTopBar } from '@/components/admin/AdminTopBar';
import type { AdminBreadcrumbItem } from '@/components/admin/AdminBreadcrumb';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import type { SharedPageProps } from '@/types';

export default function CmsBuilderLayout({
    children,
    title,
    breadcrumbs,
}: PropsWithChildren<{ title?: string; breadcrumbs?: AdminBreadcrumbItem[] }>) {
    const { auth } = usePage<SharedPageProps>().props;
    const admin = auth.admin;

    if (!admin) {
        return <>{children}</>;
    }

    return (
        <AdminTourProvider>
            <SidebarProvider defaultOpen>
                <AdminSidebar />
                <SidebarInset className="min-h-svh">
                    <AdminTopBar breadcrumbs={breadcrumbs} />
                    <main className="flex min-h-0 flex-1 flex-col overflow-hidden">
                        {title && <h1 className="sr-only">{title}</h1>}
                        {children}
                    </main>
                </SidebarInset>
            </SidebarProvider>
        </AdminTourProvider>
    );
}
