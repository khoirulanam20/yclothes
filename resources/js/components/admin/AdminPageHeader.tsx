import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function AdminPageHeader({
    title,
    description,
    backHref,
    backLabel = 'Kembali',
    createHref,
    createLabel = 'Tambah',
    actions,
}: {
    title: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
    createHref?: string;
    createLabel?: string;
    actions?: React.ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-3 mb-6">
            <div className="flex items-start gap-3 min-w-0">
                {backHref && (
                    <Button variant="outline" size="icon" className="shrink-0 mt-0.5" asChild>
                        <Link href={backHref} aria-label={backLabel}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                )}
                <div className="min-w-0">
                    <h1 className="text-2xl font-serif font-bold">{title}</h1>
                    {description && (
                        <p className="text-sm text-muted-foreground mt-1">{description}</p>
                    )}
                </div>
            </div>
            {(actions || createHref) && (
                <div className="flex flex-wrap items-center gap-2 shrink-0">
                    {actions}
                    {createHref && (
                        <Button asChild>
                            <Link href={createHref}>{createLabel}</Link>
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
