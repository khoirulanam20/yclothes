import { Link } from '@inertiajs/react';

type Crumb = { label: string; href?: string };

export function Breadcrumb({ items }: { items: Crumb[] }) {
    return (
        <nav className="flex items-center gap-1.5 text-sm text-muted-foreground mb-4">
            {items.map((item, i) => (
                <span key={i} className="flex items-center gap-1.5">
                    {i > 0 && <span>/</span>}
                    {item.href ? (
                        <Link href={item.href} className="hover:text-primary transition-colors">
                            {item.label}
                        </Link>
                    ) : (
                        <span className="text-foreground font-medium">{item.label}</span>
                    )}
                </span>
            ))}
        </nav>
    );
}
