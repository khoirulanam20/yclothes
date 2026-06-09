import {
    AlertCircle,
    Ban,
    CheckCircle2,
    Clock,
    CreditCard,
    Package,
    RotateCcw,
    Truck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { orderStatusLabels, paymentStatusLabels } from '@/lib/order-status';

type StatusTone = {
    icon: LucideIcon;
    accent: string;
    badge: string;
    ring: string;
};

const orderTone: Record<string, StatusTone> = {
    pending: {
        icon: Clock,
        accent: 'text-amber-700',
        badge: 'bg-amber-100 text-amber-800 border-amber-200',
        ring: 'bg-amber-100 text-amber-700',
    },
    awaiting_verification: {
        icon: Clock,
        accent: 'text-amber-700',
        badge: 'bg-amber-100 text-amber-800 border-amber-200',
        ring: 'bg-amber-100 text-amber-700',
    },
    confirmed: {
        icon: CheckCircle2,
        accent: 'text-emerald-700',
        badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        ring: 'bg-emerald-100 text-emerald-700',
    },
    processed: {
        icon: Package,
        accent: 'text-blue-700',
        badge: 'bg-blue-100 text-blue-800 border-blue-200',
        ring: 'bg-blue-100 text-blue-700',
    },
    shipped: {
        icon: Truck,
        accent: 'text-blue-700',
        badge: 'bg-blue-100 text-blue-800 border-blue-200',
        ring: 'bg-blue-100 text-blue-700',
    },
    delivered: {
        icon: Truck,
        accent: 'text-emerald-700',
        badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        ring: 'bg-emerald-100 text-emerald-700',
    },
    completed: {
        icon: CheckCircle2,
        accent: 'text-emerald-700',
        badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        ring: 'bg-emerald-100 text-emerald-700',
    },
    return: {
        icon: RotateCcw,
        accent: 'text-orange-700',
        badge: 'bg-orange-100 text-orange-800 border-orange-200',
        ring: 'bg-orange-100 text-orange-700',
    },
    cancelled: {
        icon: Ban,
        accent: 'text-red-700',
        badge: 'bg-red-100 text-red-800 border-red-200',
        ring: 'bg-red-100 text-red-700',
    },
};

const paymentTone: Record<string, StatusTone> = {
    pending: {
        icon: CreditCard,
        accent: 'text-amber-700',
        badge: 'bg-amber-50 text-amber-800 border-amber-200',
        ring: 'bg-amber-100 text-amber-700',
    },
    paid: {
        icon: CheckCircle2,
        accent: 'text-emerald-700',
        badge: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        ring: 'bg-emerald-100 text-emerald-700',
    },
    failed: {
        icon: AlertCircle,
        accent: 'text-red-700',
        badge: 'bg-red-50 text-red-800 border-red-200',
        ring: 'bg-red-100 text-red-700',
    },
    expired: {
        icon: AlertCircle,
        accent: 'text-red-700',
        badge: 'bg-red-50 text-red-800 border-red-200',
        ring: 'bg-red-100 text-red-700',
    },
};

const defaultTone: StatusTone = {
    icon: Clock,
    accent: 'text-foreground',
    badge: 'bg-muted text-foreground border-border',
    ring: 'bg-muted text-muted-foreground',
};

function statusDescription(orderStatus: string, paymentStatus: string): string {
    if (orderStatus === 'completed') {
        return 'Pesanan telah selesai. Terima kasih telah berbelanja.';
    }
    if (orderStatus === 'cancelled') {
        return 'Pesanan ini telah dibatalkan.';
    }
    if (orderStatus === 'return') {
        return 'Pesanan dalam proses retur.';
    }
    if (paymentStatus === 'pending') {
        return 'Menunggu pembayaran. Selesaikan pembayaran agar pesanan dapat diproses.';
    }
    if (paymentStatus === 'expired' || paymentStatus === 'failed') {
        return 'Pembayaran tidak berhasil. Hubungi kami jika membutuhkan bantuan.';
    }
    if (orderStatus === 'shipped') {
        return 'Pesanan sedang dalam perjalanan ke alamat Anda.';
    }
    if (orderStatus === 'delivered') {
        return 'Pesanan telah sampai. Konfirmasi penerimaan jika barang sudah Anda terima.';
    }
    if (orderStatus === 'confirmed' || orderStatus === 'processed') {
        return 'Pembayaran telah dikonfirmasi. Pesanan sedang kami proses.';
    }

    return 'Pantau perkembangan pesanan melalui timeline di samping.';
}

type Props = {
    orderStatus: string;
    paymentStatus: string;
    isReplacement?: boolean;
};

function StatusPill({ label, tone }: { label: string; tone: StatusTone }) {
    const Icon = tone.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium',
                tone.badge,
            )}
        >
            <Icon className="size-3.5" />
            {label}
        </span>
    );
}

export function OrderStatusOverview({ orderStatus, paymentStatus, isReplacement }: Props) {
    const order = orderTone[orderStatus] ?? defaultTone;
    const payment = paymentTone[paymentStatus] ?? defaultTone;
    const OrderIcon = order.icon;
    const orderLabel = orderStatusLabels[orderStatus] ?? orderStatus;
    const paymentLabel = paymentStatusLabels[paymentStatus] ?? paymentStatus;

    return (
        <div className="mb-6 overflow-hidden rounded-xl border bg-card shadow-sm">
            <div className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-6">
                <div
                    className={cn(
                        'flex size-12 shrink-0 items-center justify-center rounded-full',
                        order.ring,
                    )}
                >
                    <OrderIcon className="size-6" />
                </div>

                <div className="min-w-0 flex-1">
                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        Status Pesanan
                    </p>
                    <p className={cn('mt-0.5 text-xl font-semibold tracking-tight', order.accent)}>
                        {orderLabel}
                    </p>
                    <p className="mt-1.5 text-sm text-muted-foreground leading-relaxed">
                        {statusDescription(orderStatus, paymentStatus)}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 sm:flex-col sm:items-end">
                    <StatusPill label={orderLabel} tone={order} />
                    <StatusPill label={paymentLabel} tone={payment} />
                    {isReplacement && (
                        <Badge variant="secondary" className="font-normal">
                            Pesanan Pengganti
                        </Badge>
                    )}
                </div>
            </div>
        </div>
    );
}
