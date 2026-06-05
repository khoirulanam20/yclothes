import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button, buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export function GuestConfirmDeleteButton({
    href,
    name,
    variant = 'outline',
    label = 'Hapus',
}: {
    href: string;
    name: string;
    variant?: 'destructive' | 'outline' | 'ghost';
    label?: string;
}) {
    const [open, setOpen] = useState(false);

    const handleConfirm = () => {
        setOpen(false);
        router.delete(href, { preserveScroll: true });
    };

    return (
        <>
            <Button type="button" variant={variant} size="sm" onClick={() => setOpen(true)}>
                {label}
            </Button>
            <AlertDialog open={open} onOpenChange={setOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus {name}?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            className={cn(buttonVariants({ variant: 'destructive' }))}
                            onClick={handleConfirm}
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
