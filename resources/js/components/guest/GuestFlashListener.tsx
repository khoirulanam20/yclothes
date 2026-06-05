import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { guestToast } from '@/lib/guestToast';
import type { SharedPageProps } from '@/types';

export function GuestFlashListener() {
    const { flash } = usePage<SharedPageProps>().props;
    const lastFlash = useRef<{ success?: string; error?: string }>({});

    useEffect(() => {
        if (flash?.success && flash.success !== lastFlash.current.success) {
            guestToast.success(flash.success);
        }

        if (flash?.error && flash.error !== lastFlash.current.error) {
            guestToast.error(flash.error);
        }

        lastFlash.current = { success: flash?.success, error: flash?.error };
    }, [flash]);

    return null;
}
