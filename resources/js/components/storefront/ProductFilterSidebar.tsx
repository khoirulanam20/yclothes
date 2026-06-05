import { Link, router } from '@inertiajs/react';
import { ChevronDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { CategoryNav } from '@/types';

type Filters = {
    search?: string;
    category?: string;
    sort?: string;
    min_price?: string;
    max_price?: string;
};

type Props = {
    categories: CategoryNav[];
    filters: Filters;
};

function findCategory(tree: CategoryNav[], slug: string): CategoryNav | null {
    for (const cat of tree) {
        if (cat.slug === slug) {
            return cat;
        }
        if (cat.children?.length) {
            const found = findCategory(cat.children, slug);
            if (found) {
                return found;
            }
        }
    }

    return null;
}

function findParent(tree: CategoryNav[], slug: string, parent: CategoryNav | null = null): CategoryNav | null {
    for (const cat of tree) {
        if (cat.slug === slug) {
            return parent;
        }
        if (cat.children?.length) {
            const found = findParent(cat.children, slug, cat);
            if (found !== null) {
                return found;
            }
        }
    }

    return null;
}

function collectOpenSlugs(tree: CategoryNav[], slug: string): string[] {
    const open: string[] = [];

    const walk = (nodes: CategoryNav[], ancestors: string[]): boolean => {
        for (const node of nodes) {
            if (node.slug === slug) {
                open.push(...ancestors);

                return true;
            }

            if (node.children?.length && walk(node.children, [...ancestors, node.slug])) {
                return true;
            }
        }

        return false;
    };

    walk(tree, []);

    return open;
}

function buildFilterUrl(filters: Filters, overrides: Partial<Filters> = {}) {
    const next = { ...filters, ...overrides };
    const params: Record<string, string> = {};

    if (next.search) {
        params.search = next.search;
    }
    if (next.category) {
        params.category = next.category;
    }
    if (next.sort) {
        params.sort = next.sort;
    }
    if (next.min_price) {
        params.min_price = next.min_price;
    }
    if (next.max_price) {
        params.max_price = next.max_price;
    }

    return `/products?${new URLSearchParams(params).toString()}`;
}

function CategoryFilterLink({
    category,
    activeSlug,
    filters,
    className,
}: {
    category: CategoryNav;
    activeSlug?: string;
    filters: Filters;
    className?: string;
}) {
    const isActive = category.slug === activeSlug;

    return (
        <Link
            href={buildFilterUrl(filters, { category: category.slug })}
            className={cn(
                'block py-2.5 text-sm transition-colors hover:text-primary',
                isActive ? 'font-medium text-primary' : 'text-foreground',
                className,
            )}
        >
            {category.name}
        </Link>
    );
}

function CategoryFilterItem({
    category,
    activeSlug,
    filters,
}: {
    category: CategoryNav;
    activeSlug?: string;
    filters: Filters;
}) {
    const hasChildren = (category.children?.length ?? 0) > 0;

    if (!hasChildren) {
        return (
            <div className="border-b border-border/60 last:border-b-0">
                <CategoryFilterLink
                    category={category}
                    activeSlug={activeSlug}
                    filters={filters}
                    className="pr-1"
                />
            </div>
        );
    }

    return (
        <AccordionItem value={category.slug} className="border-b border-border/60 last:border-b-0">
            <div className="flex items-center gap-1">
                <CategoryFilterLink
                    category={category}
                    activeSlug={activeSlug}
                    filters={filters}
                    className="min-w-0 flex-1"
                />
                <AccordionTrigger className="group shrink-0 px-2 py-2.5 hover:no-underline [&>svg:last-child]:hidden">
                    <ChevronDown className="h-4 w-4 text-muted-foreground transition-transform duration-200 group-data-[state=open]:rotate-180" />
                </AccordionTrigger>
            </div>
            <AccordionContent className="pb-0">
                <div className="border-t border-border/40 pl-3">
                    {category.children!.map((child) => (
                        <CategoryFilterItem
                            key={child.id}
                            category={child}
                            activeSlug={activeSlug}
                            filters={filters}
                        />
                    ))}
                </div>
            </AccordionContent>
        </AccordionItem>
    );
}

export function ProductFilterSidebar({ categories, filters }: Props) {
    const activeSlug = filters.category;

    const sidebarContext = useMemo(() => {
        if (!activeSlug) {
            return { title: 'Kategori', items: categories };
        }

        const active = findCategory(categories, activeSlug);
        if (!active) {
            return { title: 'Kategori', items: categories };
        }

        if ((active.children?.length ?? 0) > 0) {
            return { title: active.name, items: active.children! };
        }

        const parent = findParent(categories, activeSlug);
        if (parent) {
            return { title: parent.name, items: parent.children ?? [] };
        }

        return { title: active.name, items: [] };
    }, [categories, activeSlug]);

    const defaultOpen = useMemo(
        () => (activeSlug ? collectOpenSlugs(categories, activeSlug) : []),
        [categories, activeSlug],
    );

    const [search, setSearch] = useState(filters.search ?? '');
    const [minPrice, setMinPrice] = useState(filters.min_price ?? '');
    const [maxPrice, setMaxPrice] = useState(filters.max_price ?? '');

    const applyFilters = (overrides: Partial<Filters> = {}) => {
        router.get(
            buildFilterUrl(filters, {
                search,
                min_price: minPrice,
                max_price: maxPrice,
                ...overrides,
            }),
            {},
            { preserveState: true },
        );
    };

    return (
        <aside className="w-full shrink-0 lg:sticky lg:top-20 lg:z-30 lg:w-64 lg:self-start">
            <div className="max-h-[calc(100vh-5.5rem)] overflow-y-auto rounded-lg border bg-card shadow-sm">
                <div className="border-b bg-muted/60 px-4 py-3">
                    <h2 className="text-sm font-bold text-foreground">Filter</h2>
                </div>

                <div className="px-4 py-3">
                    <p className="mb-2 text-sm font-bold leading-snug">{sidebarContext.title}</p>

                    <Accordion
                        type="multiple"
                        defaultValue={defaultOpen}
                        className="border-t border-border/60"
                    >
                        {sidebarContext.items.map((cat) => (
                            <CategoryFilterItem
                                key={cat.id}
                                category={cat}
                                activeSlug={activeSlug}
                                filters={filters}
                            />
                        ))}
                    </Accordion>

                    {!sidebarContext.items.length && activeSlug && findCategory(categories, activeSlug) && (
                        <CategoryFilterLink
                            category={findCategory(categories, activeSlug)!}
                            activeSlug={activeSlug}
                            filters={filters}
                        />
                    )}
                </div>

                <div className="border-t px-4 py-3">
                    <p className="mb-2 text-sm font-bold">Cari di kategori ini</p>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                        className="flex overflow-hidden rounded-md border"
                    >
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari"
                            className="h-9 flex-1 rounded-none border-0 shadow-none focus-visible:ring-0"
                        />
                        <Button
                            type="submit"
                            variant="secondary"
                            size="icon"
                            className="h-9 w-10 shrink-0 rounded-none border-l"
                        >
                            <Search className="h-4 w-4" />
                        </Button>
                    </form>
                </div>

                <div className="border-t px-4 py-3">
                    <p className="mb-2 text-sm font-bold">Harga</p>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                        className="space-y-2"
                    >
                        <div className="relative">
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted-foreground">
                                Rp
                            </span>
                            <Input
                                type="text"
                                inputMode="numeric"
                                value={minPrice}
                                onChange={(e) => setMinPrice(e.target.value.replace(/\D/g, ''))}
                                placeholder="Harga Minimum"
                                className="h-9 pl-9"
                            />
                        </div>
                        <div className="relative">
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted-foreground">
                                Rp
                            </span>
                            <Input
                                type="text"
                                inputMode="numeric"
                                value={maxPrice}
                                onChange={(e) => setMaxPrice(e.target.value.replace(/\D/g, ''))}
                                placeholder="Harga Maksimum"
                                className="h-9 pl-9"
                            />
                        </div>
                        <input type="hidden" name="sort" value={filters.sort ?? ''} />
                        <Button type="submit" variant="outline" size="sm" className="w-full">
                            Terapkan
                        </Button>
                    </form>
                </div>
            </div>
        </aside>
    );
}
