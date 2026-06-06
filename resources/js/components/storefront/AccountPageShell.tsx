import { PropsWithChildren, ReactNode } from 'react';
import { SectionCard } from '@/components/storefront/SectionCard';
import { cn } from '@/lib/utils';

type Props = PropsWithChildren<{
    title?: string;
    description?: string;
    actions?: ReactNode;
    className?: string;
    noPadding?: boolean;
}>;

export function AccountPageShell({
    title,
    description,
    actions,
    className,
    noPadding,
    children,
}: Props) {
    return (
        <SectionCard noPadding={noPadding} className={cn('overflow-hidden', className)}>
            {(title || description || actions) && (
                <div className="flex flex-wrap items-start justify-between gap-3 border-b px-4 py-4 sm:px-6">
                    <div>
                        {title && <h2 className="text-lg font-semibold">{title}</h2>}
                        {description && (
                            <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                        )}
                    </div>
                    {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
                </div>
            )}
            <div className={cn(!noPadding && 'p-4 sm:p-6')}>{children}</div>
        </SectionCard>
    );
}
