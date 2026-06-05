import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { cn } from '@/lib/utils';
import type { CategoryNav, SharedPageProps } from '@/types';

function SubCategoryPanel({ category }: { category: CategoryNav }) {
    const children = category.children ?? [];

    if (children.length === 0) {
        return (
            <Link
                href={`/products?category=${category.slug}`}
                className="text-sm text-muted-foreground hover:text-primary"
            >
                Lihat semua {category.name}
            </Link>
        );
    }

    const groups = children.filter((c) => (c.children?.length ?? 0) > 0);
    const leaves = children.filter((c) => !(c.children?.length ?? 0));

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-2 gap-x-8 gap-y-5 sm:grid-cols-3 md:grid-cols-4">
                {groups.map((group) => (
                    <div key={group.id}>
                        <Link
                            href={`/products?category=${group.slug}`}
                            className="mb-2 block text-sm font-semibold text-foreground hover:text-primary transition-colors"
                        >
                            {group.name}
                        </Link>
                        <ul className="space-y-1.5">
                            {group.children!.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={`/products?category=${item.slug}`}
                                        className="text-sm text-muted-foreground hover:text-primary"
                                    >
                                        {item.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}

                {leaves.map((item) => (
                    <Link
                        key={item.id}
                        href={`/products?category=${item.slug}`}
                        className="text-sm text-muted-foreground hover:text-primary"
                    >
                        {item.name}
                    </Link>
                ))}
            </div>
        </div>
    );
}

export function CategoryMenu() {
    const { categories } = usePage<SharedPageProps>().props;
    const [open, setOpen] = useState(false);
    const [activeId, setActiveId] = useState<number | null>(null);

    const rootCategories = useMemo(
        () => categories.filter((cat) => cat.parentId == null),
        [categories],
    );

    const activeCategory =
        rootCategories.find((cat) => cat.id === activeId) ?? rootCategories[0] ?? null;

    useEffect(() => {
        if (open && rootCategories.length > 0) {
            setActiveId((prev) => prev ?? rootCategories[0].id);
        }
        if (!open) {
            setActiveId(null);
        }
    }, [open, rootCategories]);

    if (!rootCategories.length) {
        return null;
    }

    return (
        <div
            className="relative"
            onMouseEnter={() => setOpen(true)}
            onMouseLeave={() => setOpen(false)}
        >
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className={cn(
                    'flex h-10 shrink-0 items-center whitespace-nowrap rounded-l-full border-r border-border px-3 text-sm font-medium transition-colors sm:px-4',
                    open ? 'bg-primary/10 text-primary' : 'text-foreground hover:bg-muted/80',
                )}
                aria-expanded={open}
                aria-haspopup="true"
            >
                Kategori
            </button>

            <div
                className={cn(
                    'absolute left-0 top-full z-50 pt-2 transition-all duration-150',
                    open
                        ? 'visible translate-y-0 opacity-100'
                        : 'invisible pointer-events-none -translate-y-1 opacity-0',
                )}
            >
                <div className="flex max-h-[min(420px,70vh)] w-[min(720px,calc(100vw-2rem))] overflow-hidden rounded-lg border bg-card shadow-lg">
                    <aside className="flex w-44 shrink-0 flex-col overflow-y-auto border-r bg-muted/40 py-2">
                        <Link
                            href="/products"
                            className="mx-2 mb-1 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-primary transition-colors"
                        >
                            Semua Produk
                        </Link>

                        {rootCategories.map((cat) => (
                            <Link
                                key={cat.id}
                                href={`/products?category=${cat.slug}`}
                                onMouseEnter={() => setActiveId(cat.id)}
                                onFocus={() => setActiveId(cat.id)}
                                className={cn(
                                    'mx-2 rounded-md px-3 py-2.5 text-sm transition-colors hover:text-primary',
                                    activeCategory?.id === cat.id
                                        ? 'font-medium text-primary'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {cat.name}
                            </Link>
                        ))}
                    </aside>

                    {activeCategory && (
                        <div className="min-w-0 flex-1 overflow-y-auto p-5">
                            <Link
                                href={`/products?category=${activeCategory.slug}`}
                                className="mb-4 block text-base font-bold text-foreground hover:text-primary transition-colors"
                            >
                                {activeCategory.name}
                            </Link>
                            <SubCategoryPanel category={activeCategory} />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
