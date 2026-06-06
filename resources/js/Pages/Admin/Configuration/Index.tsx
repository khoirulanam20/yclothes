import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminContent } from '@/components/admin/AdminContent';
import { AdminPageHeader } from '@/components/admin/AdminPageHeader';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { configurationBreadcrumbs } from '@/lib/configuration-nav';
import { cn } from '@/lib/utils';

type Child = {
    key: string;
    name: string;
    info?: string | null;
    type: 'form' | 'link';
    href: string;
};

type Category = {
    key: string;
    name: string;
    info?: string | null;
    children: Child[];
};

type SearchResult = {
    key: string;
    name: string;
    info?: string | null;
    category: string;
    href: string;
};

type Props = {
    categories: Category[];
};

export default function Index({ categories }: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (query.length < 2) {
            setResults([]);
            setOpen(false);
            return;
        }

        const timer = setTimeout(async () => {
            setLoading(true);
            try {
                const res = await fetch(`/admin/configuration/search?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                setResults(data);
                setOpen(true);
            } finally {
                setLoading(false);
            }
        }, 250);

        return () => clearTimeout(timer);
    }, [query]);

    useEffect(() => {
        const handleClick = (e: MouseEvent) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        window.addEventListener('click', handleClick);
        return () => window.removeEventListener('click', handleClick);
    }, []);

    return (
        <AdminLayout title="Konfigurasi" breadcrumbs={configurationBreadcrumbs()}>
            <Head title="Konfigurasi" />

            <AdminContent>
            <AdminPageHeader
                title="Konfigurasi"
                description="Kelola semua pengaturan toko dari satu tempat."
            />

            <div ref={wrapperRef} className="relative mb-8 max-w-lg">
                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    className="pl-9"
                    placeholder="Cari konfigurasi..."
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={() => query.length >= 2 && setOpen(true)}
                />
                {open && (
                    <div className="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-md">
                        {loading && <p className="px-4 py-3 text-sm text-muted-foreground">Mencari...</p>}
                        {!loading && results.length === 0 && (
                            <p className="px-4 py-3 text-sm text-muted-foreground">Tidak ditemukan.</p>
                        )}
                        {!loading && results.map((r) => (
                            <Link
                                key={r.key}
                                href={r.href}
                                className="block px-4 py-3 hover:bg-muted/60 border-b last:border-0"
                                onClick={() => setOpen(false)}
                            >
                                <span className="font-medium text-sm">{r.name}</span>
                                <span className="block text-xs text-muted-foreground">{r.category}</span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {categories.map((category) => (
                    <Card key={category.key}>
                        <CardHeader>
                            <CardTitle>{category.name}</CardTitle>
                            {category.info && (
                                <p className="text-sm text-muted-foreground">{category.info}</p>
                            )}
                        </CardHeader>
                        <CardContent className="divide-y">
                            {category.children.map((child) => (
                                <Link
                                    key={child.key}
                                    href={child.href}
                                    className={cn(
                                        'flex items-center justify-between py-3 first:pt-0 last:pb-0',
                                        'group hover:text-primary transition-colors',
                                    )}
                                >
                                    <div>
                                        <span className="font-medium text-sm">{child.name}</span>
                                        {child.info && (
                                            <p className="text-xs text-muted-foreground mt-0.5 line-clamp-1">{child.info}</p>
                                        )}
                                    </div>
                                    <ChevronRight className="size-4 shrink-0 text-muted-foreground group-hover:text-primary" />
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                ))}
            </div>
            </AdminContent>
        </AdminLayout>
    );
}
