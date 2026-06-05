import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

export function FlashToaster() {
    const { flash } = usePage().props as { flash: { success?: string; error?: string } };

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    return null;
}
