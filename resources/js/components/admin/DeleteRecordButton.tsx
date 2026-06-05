import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useAdminConfirm } from '@/hooks/use-admin-confirm';

export function DeleteRecordButton({
    href,
    name,
    variant = 'destructive',
    description,
}: {
    href: string;
    name: string;
    variant?: 'destructive' | 'outline';
    description?: string;
}) {
    const confirm = useAdminConfirm();

    const handleDelete = async () => {
        const ok = await confirm({
            title: `Hapus ${name}?`,
            description: description ?? 'Tindakan ini tidak dapat dibatalkan.',
            confirmLabel: 'Hapus',
            cancelLabel: 'Batal',
            variant: 'destructive',
        });

        if (ok) {
            router.delete(href, { preserveScroll: true });
        }
    };

    return (
        <Button type="button" variant={variant} size="sm" onClick={handleDelete}>
            Hapus
        </Button>
    );
}
