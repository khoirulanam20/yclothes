import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

export function PaginationLinks({ pagination }: { pagination: Paginated<unknown> }) {
    if (pagination.meta.last_page <= 1) return null;

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 mt-4">
            <p className="text-sm text-muted-foreground">
                Halaman {pagination.meta.current_page} dari {pagination.meta.last_page} ({pagination.meta.total}{' '}
                total)
            </p>
            <div className="flex flex-wrap gap-1">
                {pagination.links.map((link, i) => {
                    if (!link.url) {
                        return (
                            <Button key={i} variant="outline" size="sm" disabled className="min-w-9">
                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                            </Button>
                        );
                    }
                    return (
                        <Button
                            key={i}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            asChild
                            className="min-w-9"
                        >
                            <Link href={link.url} preserveScroll>
                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                            </Link>
                        </Button>
                    );
                })}
            </div>
        </div>
    );
}
