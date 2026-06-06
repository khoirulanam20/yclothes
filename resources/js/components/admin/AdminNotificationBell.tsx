import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatBadgeCount } from '@/lib/admin-nav';
import { cn } from '@/lib/utils';
import type { SharedPageProps } from '@/types';

type AdminNotification = {
    id: number;
    type: string;
    title: string;
    body?: string | null;
    data?: Record<string, unknown> | null;
    readAt?: string | null;
    createdAt?: string;
};

function formatRelativeTime(iso?: string): string {
    if (!iso) return '';
    const date = new Date(iso);
    const diffMs = date.getTime() - Date.now();
    const diffMinutes = Math.round(diffMs / 60000);
    const rtf = new Intl.RelativeTimeFormat('id', { numeric: 'auto' });

    if (Math.abs(diffMinutes) < 60) {
        return rtf.format(diffMinutes, 'minute');
    }

    const diffHours = Math.round(diffMinutes / 60);
    if (Math.abs(diffHours) < 24) {
        return rtf.format(diffHours, 'hour');
    }

    const diffDays = Math.round(diffHours / 24);
    return rtf.format(diffDays, 'day');
}

function resolveNotificationHref(notification: AdminNotification): string | null {
    const data = notification.data ?? {};

    if (notification.type === 'return_submitted' && data.return_request_id) {
        return `/admin/returns/${data.return_request_id}`;
    }

    if (['order_created', 'payment_submitted'].includes(notification.type) && data.order_id) {
        return `/admin/orders/${data.order_id}`;
    }

    if (notification.type === 'review_submitted') {
        return '/admin/reviews?status=pending';
    }

    return null;
}

export function AdminNotificationBell() {
    const { adminBadges } = usePage<SharedPageProps>().props;
    const unread = adminBadges?.notificationsUnread ?? 0;
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [notifications, setNotifications] = useState<AdminNotification[]>([]);

    const fetchNotifications = useCallback(async () => {
        setLoading(true);
        try {
            const response = await fetch('/admin/notifications', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) {
                const data = await response.json();
                setNotifications(Array.isArray(data) ? data : []);
            }
        } finally {
            setLoading(false);
        }
    }, []);

    const handleOpenChange = (next: boolean) => {
        setOpen(next);
        if (next) {
            void fetchNotifications();
        }
    };

    const markAllRead = async (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();

        await fetch('/admin/notifications/read-all', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            },
        });
        setNotifications((current) => current.map((n) => ({ ...n, readAt: n.readAt ?? new Date().toISOString() })));
        router.reload({ only: ['adminBadges'] });
    };

    const handleItemClick = async (notification: AdminNotification) => {
        setOpen(false);

        if (!notification.readAt) {
            await fetch(`/admin/notifications/${notification.id}/read`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
            });
            router.reload({ only: ['adminBadges'] });
        }

        const href = resolveNotificationHref(notification);
        if (href) {
            router.visit(href);
        }
    };

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative shrink-0" aria-label="Notifikasi">
                    <Bell className="size-4" />
                    {unread > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-medium text-destructive-foreground">
                            {formatBadgeCount(unread)}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="w-[min(22rem,calc(100vw-2rem))] overflow-hidden border bg-background p-0 shadow-lg"
            >
                <div className="flex items-center justify-between gap-3 border-b bg-background px-4 py-3">
                    <p className="text-sm font-semibold">Notifikasi</p>
                    {unread > 0 && (
                        <button
                            type="button"
                            className="shrink-0 text-xs text-primary hover:underline"
                            onClick={(event) => void markAllRead(event)}
                        >
                            Tandai semua dibaca
                        </button>
                    )}
                </div>

                <div className="max-h-[min(24rem,70vh)] overflow-y-auto bg-background">
                    {loading && (
                        <div className="px-4 py-8 text-center text-sm text-muted-foreground">Memuat...</div>
                    )}
                    {!loading && notifications.length === 0 && (
                        <div className="px-4 py-8 text-center text-sm text-muted-foreground">Belum ada notifikasi</div>
                    )}
                    {!loading && notifications.map((notification) => {
                        const isUnread = !notification.readAt;

                        return (
                            <button
                                key={notification.id}
                                type="button"
                                className={cn(
                                    'block w-full border-b px-4 py-3 text-left transition-colors last:border-b-0',
                                    'hover:bg-muted/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
                                    isUnread ? 'bg-muted/40' : 'bg-background',
                                )}
                                onClick={() => void handleItemClick(notification)}
                            >
                                <div className="flex items-start gap-2">
                                    {isUnread && (
                                        <span
                                            className="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                                            aria-hidden
                                        />
                                    )}
                                    <div className={cn('min-w-0 flex-1 space-y-1', !isUnread && 'pl-4')}>
                                        <p className="text-sm font-medium leading-snug text-foreground">
                                            {notification.title}
                                        </p>
                                        {notification.body && (
                                            <p className="text-xs leading-relaxed text-muted-foreground line-clamp-2">
                                                {notification.body}
                                            </p>
                                        )}
                                        <p className="text-[11px] text-muted-foreground">
                                            {formatRelativeTime(notification.createdAt)}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        );
                    })}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
