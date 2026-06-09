import { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

type Props = PropsWithChildren<{
    className?: string;
    narrow?: boolean;
    compact?: boolean;
}>;

export function PageContainer({ children, className, narrow, compact }: Props) {
    return (
        <div
            className={cn(
                'container mx-auto px-4',
                compact ? 'py-4 md:py-6' : 'py-6',
                narrow && 'max-w-3xl',
                className,
            )}
        >
            {children}
        </div>
    );
}
