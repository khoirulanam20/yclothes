import { PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';
import { AdminSidebar } from '@/components/admin/AdminSidebar';
import { AdminTopBar } from '@/components/admin/AdminTopBar';
import type { AdminBreadcrumbItem } from '@/components/admin/AdminBreadcrumb';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import type { SharedPageProps } from '@/types';

export default function AdminLayout({
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
        <SidebarProvider defaultOpen>
            <AdminSidebar />
            <SidebarInset>
                <AdminTopBar breadcrumbs={breadcrumbs} />
                <main className="flex-1 w-full min-w-0 p-4 md:p-6 lg:p-8">
                    {title && <h1 className="sr-only">{title}</h1>}
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
    );
}
