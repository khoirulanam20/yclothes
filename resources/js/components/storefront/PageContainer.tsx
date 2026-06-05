import { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

type Props = PropsWithChildren<{
    className?: string;
    narrow?: boolean;
}>;

export function PageContainer({ children, className, narrow }: Props) {
    return (
        <div className={cn('container mx-auto px-4 py-6', narrow && 'max-w-3xl', className)}>
            {children}
        </div>
    );
}
