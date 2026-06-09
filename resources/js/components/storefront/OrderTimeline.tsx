import { CheckCircle2 } from 'lucide-react';
import { AccountPageShell } from '@/components/storefront/AccountPageShell';
import { cn } from '@/lib/utils';
import { orderStatusLabels } from '@/lib/order-status';

type TimelineEntry = {
    toStatus: string;
    note?: string | null;
    createdAt: string;
};

type Props = {
    entries: TimelineEntry[];
};

function formatTimelineDate(value: string): string {
    return new Date(value).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function OrderTimeline({ entries }: Props) {
    if (entries.length === 0) {
        return null;
    }

    return (
        <AccountPageShell title="Riwayat Status" description="Perkembangan pesanan dari waktu ke waktu">
            <ol className="relative space-y-0">
                {entries.map((entry, index) => {
                    const isLatest = index === 0;
                    const isLast = index === entries.length - 1;

                    return (
                        <li key={`${entry.toStatus}-${entry.createdAt}-${index}`} className="relative flex gap-4 pb-6 last:pb-0">
                            {!isLast && (
                                <span
                                    className="absolute left-[11px] top-6 h-[calc(100%-12px)] w-px bg-border"
                                    aria-hidden
                                />
                            )}

                            <div className="relative z-10 mt-0.5 shrink-0">
                                {isLatest ? (
                                    <span className="flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm">
                                        <CheckCircle2 className="size-3.5" />
                                    </span>
                                ) : (
                                    <span className="flex size-6 items-center justify-center rounded-full border-2 border-muted-foreground/20 bg-background">
                                        <span className="size-2 rounded-full bg-muted-foreground/30" />
                                    </span>
                                )}
                            </div>

                            <div className={cn('min-w-0 flex-1 pt-0.5', !isLatest && 'opacity-80')}>
                                <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                    <p className={cn('text-sm font-semibold', isLatest && 'text-foreground')}>
                                        {orderStatusLabels[entry.toStatus] ?? entry.toStatus}
                                    </p>
                                    <time className="text-xs text-muted-foreground tabular-nums">
                                        {formatTimelineDate(entry.createdAt)}
                                    </time>
                                </div>
                                {entry.note && (
                                    <p className="mt-1 text-sm text-muted-foreground leading-relaxed">
                                        {entry.note}
                                    </p>
                                )}
                            </div>
                        </li>
                    );
                })}
            </ol>
        </AccountPageShell>
    );
}
