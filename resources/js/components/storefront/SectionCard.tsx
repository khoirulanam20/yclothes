import { Link } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = PropsWithChildren<{
    title?: string;
    action?: { label: string; href: string };
    headerExtra?: ReactNode;
    className?: string;
    noPadding?: boolean;
    variant?: 'default' | 'primary';
}>;

export function SectionCard({
    children,
    title,
    action,
    headerExtra,
    className,
    noPadding,
    variant = 'default',
}: Props) {
    const hasHeader = title || action || headerExtra;

    return (
        <section
            className={cn(
                'rounded-lg bg-card shadow-sm border overflow-hidden',
                variant === 'primary' && 'border-primary/20',
                className,
            )}
        >
            {hasHeader && (
                <div
                    className={cn(
                        'flex items-center justify-between gap-4 px-4 py-3 border-b',
                        variant === 'primary' && 'bg-primary/5',
                    )}
                >
                    {title && (
                        <h2
                            className={cn(
                                'text-base font-bold',
                                variant === 'primary' && 'text-primary',
                            )}
                        >
                            {title}
                        </h2>
                    )}
                    <div className="flex items-center gap-3 ml-auto">
                        {headerExtra}
                        {action && (
                            <Link
                                href={action.href}
                                className="text-sm text-primary hover:underline whitespace-nowrap"
                            >
                                {action.label}
                            </Link>
                        )}
                    </div>
                </div>
            )}
            <div className={cn(!noPadding && 'p-4')}>{children}</div>
        </section>
    );
}
