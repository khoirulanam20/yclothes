import { router } from '@inertiajs/react';
import { useAdminConfirm } from '@/hooks/use-admin-confirm';
import { AdminDeleteAction } from '@/components/admin/AdminTableActions';

export function DeleteRecordButton({
    href,
    name,
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

    return <AdminDeleteAction onClick={() => void handleDelete()} />;
}
