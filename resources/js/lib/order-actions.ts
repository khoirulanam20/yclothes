export type OrderAction = {
    key: 'verify_payment' | 'approve_confirmation' | 'reject_confirmation' | 'process' | 'ship' | 'cancel' | 'info';
    label: string;
    variant: 'default' | 'outline' | 'destructive';
    hint?: string;
    confirmationId?: number;
};

export const ORDER_FLOW_STEPS = [
    'Bayar',
    'Verifikasi',
    'Proses',
    'Kirim',
    'Selesai',
] as const;

export function isInteractiveAction(action: OrderAction): boolean {
    return action.key !== 'info' && action.label !== '';
}
