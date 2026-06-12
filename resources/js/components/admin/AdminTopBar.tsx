import { Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { AdminNotificationBell } from '@/components/admin/AdminNotificationBell';
import { AdminTourReplayButton } from '@/components/admin/AdminTourReplayButton';
import { AdminBreadcrumb, type AdminBreadcrumbItem } from '@/components/admin/AdminBreadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';

export function AdminTopBar({ breadcrumbs }: { breadcrumbs?: AdminBreadcrumbItem[] }) {
    return (
        <header className="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-2 border-b bg-background px-4">
            <SidebarTrigger className="-ml-1" />
            <Separator orientation="vertical" className="mr-2 h-4" />
            <div className="flex flex-1 items-center min-w-0">
                <AdminBreadcrumb items={breadcrumbs} />
            </div>
            <div className="flex items-center gap-1 shrink-0">
                <AdminTourReplayButton />
                <div data-tour="notifications">
                    <AdminNotificationBell />
                </div>
                <Link
                    href="/"
                    className="hidden sm:flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ExternalLink className="size-4" />
                    Lihat Toko
                </Link>
            </div>
        </header>
    );
}
