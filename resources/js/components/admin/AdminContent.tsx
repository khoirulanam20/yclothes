import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type AdminContentProps = {
    children: React.ReactNode;
    className?: string;
};

export function AdminContent({ children, className }: AdminContentProps) {
    return (
        <div className={cn('w-full min-w-0 space-y-6', className)}>
            {children}
        </div>
    );
}

type AdminFormGridProps = {
    children: React.ReactNode;
    className?: string;
    columns?: 2 | 3;
};

export function AdminFormGrid({ children, className, columns = 3 }: AdminFormGridProps) {
    return (
        <div
            className={cn(
                'grid gap-5',
                columns === 2 ? 'md:grid-cols-2' : 'md:grid-cols-2 xl:grid-cols-3',
                className,
            )}
        >
            {children}
        </div>
    );
}

type AdminFormCardProps = {
    children: React.ReactNode;
    footer?: React.ReactNode;
    className?: string;
    contentClassName?: string;
};

export function AdminFormCard({ children, footer, className, contentClassName }: AdminFormCardProps) {
    return (
        <Card className={cn('w-full', className)}>
            <CardContent className={cn('p-6', contentClassName)}>
                {children}
            </CardContent>
            {footer && (
                <div className="flex flex-wrap items-center justify-end gap-3 border-t bg-muted/20 px-6 py-4">
                    {footer}
                </div>
            )}
        </Card>
    );
}

export function AdminTableScroll({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <div className={cn('overflow-x-auto', className)}>
            {children}
        </div>
    );
}

type AdminCheckboxRowProps = {
    id: string;
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    className?: string;
};

export function AdminCheckboxRow({ id, label, checked, onChange, className }: AdminCheckboxRowProps) {
    return (
        <label
            htmlFor={id}
            className={cn(
                'flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border bg-background px-4 py-2',
                className,
            )}
        >
            <input
                id={id}
                type="checkbox"
                className="size-5 shrink-0 rounded border"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
            />
            <span className="text-sm">{label}</span>
        </label>
    );
}
