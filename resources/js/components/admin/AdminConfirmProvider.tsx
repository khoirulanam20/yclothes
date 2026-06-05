import { createContext, useCallback, useContext, useRef, useState, type PropsWithChildren } from 'react';
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
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type AdminConfirmOptions = {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'destructive';
};

type ConfirmState = AdminConfirmOptions & {
    open: boolean;
};

type AdminConfirmContextValue = {
    confirm: (options: AdminConfirmOptions) => Promise<boolean>;
};

const AdminConfirmContext = createContext<AdminConfirmContextValue | null>(null);

export function AdminConfirmProvider({ children }: PropsWithChildren) {
    const [state, setState] = useState<ConfirmState>({
        open: false,
        title: '',
        description: '',
        confirmLabel: 'Ya',
        cancelLabel: 'Batal',
        variant: 'default',
    });
    const resolverRef = useRef<((value: boolean) => void) | null>(null);

    const confirm = useCallback((options: AdminConfirmOptions) => {
        return new Promise<boolean>((resolve) => {
            resolverRef.current = resolve;
            setState({
                open: true,
                title: options.title,
                description: options.description ?? '',
                confirmLabel: options.confirmLabel ?? 'Ya',
                cancelLabel: options.cancelLabel ?? 'Batal',
                variant: options.variant ?? 'default',
            });
        });
    }, []);

    const close = (result: boolean) => {
        setState((current) => ({ ...current, open: false }));
        resolverRef.current?.(result);
        resolverRef.current = null;
    };

    const isDestructive = state.variant === 'destructive';

    return (
        <AdminConfirmContext.Provider value={{ confirm }}>
            {children}
            <AlertDialog open={state.open} onOpenChange={(open) => !open && close(false)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{state.title}</AlertDialogTitle>
                        {state.description ? (
                            <AlertDialogDescription>{state.description}</AlertDialogDescription>
                        ) : null}
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => close(false)}>{state.cancelLabel}</AlertDialogCancel>
                        <AlertDialogAction
                            className={cn(isDestructive && buttonVariants({ variant: 'destructive' }))}
                            onClick={() => close(true)}
                        >
                            {state.confirmLabel}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminConfirmContext.Provider>
    );
}

export function useAdminConfirm(): AdminConfirmContextValue['confirm'] {
    const context = useContext(AdminConfirmContext);

    if (!context) {
        throw new Error('useAdminConfirm must be used within AdminConfirmProvider');
    }

    return context.confirm;
}
