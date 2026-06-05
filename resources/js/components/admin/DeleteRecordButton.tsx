import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

export function DeleteRecordButton({
    href,
    name,
    variant = 'destructive',
}: {
    href: string;
    name: string;
    variant?: 'destructive' | 'outline';
}) {
    const [confirming, setConfirming] = useState(false);

    if (!confirming) {
        return (
            <Button type="button" variant={variant} size="sm" onClick={() => setConfirming(true)}>
                Hapus
            </Button>
        );
    }

    return (
        <div className="flex gap-1">
            <Button
                type="button"
                variant="destructive"
                size="sm"
                onClick={() => router.delete(href, { preserveScroll: true })}
            >
                Ya
            </Button>
            <Button type="button" variant="outline" size="sm" onClick={() => setConfirming(false)}>
                Batal
            </Button>
            <span className="sr-only">{name}</span>
        </div>
    );
}
