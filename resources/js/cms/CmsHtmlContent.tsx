import { cn } from '@/lib/utils';
import { normalizeCmsHtml } from '@/cms/normalizeCmsHtml';

type Props = {
    html: string;
    className?: string;
    emptyLabel?: string;
    /** Tanpa wrapper container — untuk grid/kolom */
    bare?: boolean;
};

export function CmsHtmlContent({ html, className, emptyLabel = 'Tambahkan konten', bare = false }: Props) {
    if (!html?.trim()) {
        if (bare) {
            return (
                <div className="rounded-lg border border-dashed bg-muted/50 px-4 py-8 text-center text-sm text-muted-foreground">
                    {emptyLabel}
                </div>
            );
        }

        return (
            <div className="container mx-auto px-4 py-4">
                <div className="rounded-lg border border-dashed bg-muted/50 px-4 py-8 text-center text-sm text-muted-foreground">
                    {emptyLabel}
                </div>
            </div>
        );
    }

    const content = (
        <div
            className={cn('cms-content max-w-none', className)}
            dangerouslySetInnerHTML={{ __html: normalizeCmsHtml(html) }}
        />
    );

    if (bare) {
        return content;
    }

    return <div className="container mx-auto px-4 py-4">{content}</div>;
}
