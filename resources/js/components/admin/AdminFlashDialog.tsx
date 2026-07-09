import { AlertCircle, AlertTriangle, CheckCircle2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { adminAlert, subscribeAdminAlert, type AdminAlertPayload } from '@/lib/adminAlert';
import type { SharedPageProps } from '@/types';

type AlertState = AdminAlertPayload & { open: boolean };

const defaultAlert: AlertState = {
    open: false,
    type: 'success',
    title: '',
    message: '',
};

export function AdminFlashDialog() {
    const { flash } = usePage<SharedPageProps>().props;
    const [alert, setAlert] = useState<AlertState>(defaultAlert);
    const lastFlash = useRef<{ success?: string; error?: string; warning?: string }>({});

    const showAlert = (payload: AdminAlertPayload) => {
        setAlert({ ...payload, open: true });
    };

    useEffect(() => {
        return subscribeAdminAlert(showAlert);
    }, []);

    useEffect(() => {
        if (flash?.success && flash.success !== lastFlash.current.success) {
            showAlert({ type: 'success', title: 'Berhasil', message: flash.success });
        }

        if (flash?.error && flash.error !== lastFlash.current.error) {
            showAlert({ type: 'error', title: 'Terjadi Kesalahan', message: flash.error });
        }

        if (flash?.warning && flash.warning !== lastFlash.current.warning) {
            showAlert({ type: 'warning', title: 'Perhatian', message: flash.warning });
        }

        lastFlash.current = {
            success: flash?.success,
            error: flash?.error,
            warning: flash?.warning,
        };
    }, [flash]);

    const Icon = alert.type === 'success'
        ? CheckCircle2
        : alert.type === 'warning'
            ? AlertTriangle
            : AlertCircle;
    const iconClass = alert.type === 'success'
        ? 'text-green-600'
        : alert.type === 'warning'
            ? 'text-amber-600'
            : 'text-destructive';

    return (
        <AlertDialog open={alert.open} onOpenChange={(open) => setAlert((current) => ({ ...current, open }))}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div className="flex items-start gap-3">
                        <Icon className={`h-5 w-5 shrink-0 mt-0.5 ${iconClass}`} />
                        <div className="space-y-1">
                            <AlertDialogTitle>{alert.title}</AlertDialogTitle>
                            <AlertDialogDescription>{alert.message}</AlertDialogDescription>
                        </div>
                    </div>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogAction onClick={() => setAlert((current) => ({ ...current, open: false }))}>
                        OK
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
