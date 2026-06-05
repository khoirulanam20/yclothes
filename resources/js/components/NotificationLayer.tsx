import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { AdminFlashDialog } from '@/components/admin/AdminFlashDialog';
import { GuestFlashListener } from '@/components/guest/GuestFlashListener';
import { GuestToaster } from '@/components/guest/GuestToaster';
import { adminAlert } from '@/lib/adminAlert';
import { guestToast } from '@/lib/guestToast';

function isAdminRoute(url: string): boolean {
    return url.startsWith('/admin');
}

function firstValidationError(errors: Record<string, string | string[]>): string | null {
    const values = Object.values(errors);

    for (const value of values) {
        if (Array.isArray(value) && value[0]) {
            return value[0];
        }

        if (typeof value === 'string' && value) {
            return value;
        }
    }

    return null;
}

export function NotificationLayer() {
    const { url } = usePage();
    const isAdmin = isAdminRoute(url);

    useEffect(() => {
        const removeError = router.on('error', (event) => {
            const message = firstValidationError(event.detail.errors);

            if (!message) {
                return;
            }

            if (isAdminRoute(window.location.pathname)) {
                adminAlert.error(message);
            } else {
                guestToast.error(message);
            }
        });

        const removeInvalid = router.on('invalid', () => {
            const message = 'Terjadi kesalahan. Silakan coba lagi.';

            if (isAdminRoute(window.location.pathname)) {
                adminAlert.error(message);
            } else {
                guestToast.error(message);
            }
        });

        const removeException = router.on('exception', () => {
            const message = 'Terjadi kesalahan. Silakan coba lagi.';

            if (isAdminRoute(window.location.pathname)) {
                adminAlert.error(message);
            } else {
                guestToast.error(message);
            }
        });

        return () => {
            removeError();
            removeInvalid();
            removeException();
        };
    }, []);

    if (isAdmin) {
        return <AdminFlashDialog />;
    }

    return (
        <>
            <GuestToaster />
            <GuestFlashListener />
        </>
    );
}
