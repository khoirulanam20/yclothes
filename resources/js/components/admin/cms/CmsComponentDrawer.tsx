import { createUsePuck } from '@measured/puck';
import { Plus, Search } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { insertBlockAtEnd } from '@/cms/insertBlock';
import type { CmsBlockCategory, CmsBlockDefinition } from '@/cms/registry';
import type { PuckData } from '@/cms/puckConfig';
import { cn } from '@/lib/utils';

const usePuck = createUsePuck();

const CATEGORY_LABELS: Record<CmsBlockCategory, string> = {
    layout: 'Layout',
    content: 'Konten',
};

type Props = {
    blocks: CmsBlockDefinition[];
    children?: ReactNode;
};

export function CmsComponentDrawer({ blocks, children }: Props) {
    const dispatch = usePuck((s) => s.dispatch);
    const data = usePuck((s) => s.appState.data as PuckData);
    const [query, setQuery] = useState('');
    const [expanded, setExpanded] = useState<Record<CmsBlockCategory, boolean>>({
        layout: true,
        content: true,
    });

    const filteredBlocks = useMemo(() => {
        const q = query.trim().toLowerCase();

        if (!q) {
            return blocks;
        }

        return blocks.filter(
            (block) =>
                block.label.toLowerCase().includes(q) ||
                block.description?.toLowerCase().includes(q) ||
                block.type.toLowerCase().includes(q),
        );
    }, [blocks, query]);

    const grouped = useMemo(() => {
        const groups: Record<CmsBlockCategory, CmsBlockDefinition[]> = {
            layout: [],
            content: [],
        };

        for (const block of filteredBlocks) {
            groups[block.category].push(block);
        }

        return groups;
    }, [filteredBlocks]);

    const handleInsert = (block: CmsBlockDefinition) => {
        insertBlockAtEnd(dispatch, data, block);
    };

    return (
        <div className="flex h-full flex-col bg-background" data-tour="cms-builder-components">
            <div className="border-b p-3">
                <p className="mb-2 text-sm font-semibold">Komponen</p>
                <div className="relative">
                    <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Cari komponen..."
                        className="h-9 pl-8"
                    />
                </div>
            </div>

            <div className="flex-1 overflow-y-auto p-2">
                {(Object.keys(CATEGORY_LABELS) as CmsBlockCategory[]).map((category) => {
                    const items = grouped[category];

                    if (items.length === 0) {
                        return null;
                    }

                    return (
                        <div key={category} className="mb-3">
                            <button
                                type="button"
                                onClick={() =>
                                    setExpanded((prev) => ({ ...prev, [category]: !prev[category] }))
                                }
                                className="mb-1 flex w-full items-center justify-between px-2 py-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground"
                            >
                                {CATEGORY_LABELS[category]}
                                <span className="text-[10px]">{expanded[category] ? '−' : '+'}</span>
                            </button>

                            {expanded[category] && (
                                <div className="space-y-1">
                                    {items.map((block) => (
                                        <div
                                            key={block.type}
                                            className="group flex items-start gap-2 rounded-md border bg-card p-2 transition-colors hover:border-primary/40"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium">{block.label}</p>
                                                {block.description && (
                                                    <p className="mt-0.5 text-xs text-muted-foreground line-clamp-2">
                                                        {block.description}
                                                    </p>
                                                )}
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                className={cn(
                                                    'h-8 w-8 shrink-0',
                                                    'opacity-80 group-hover:opacity-100',
                                                )}
                                                title={`Tambah ${block.label}`}
                                                onClick={() => handleInsert(block)}
                                            >
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    );
                })}

                {filteredBlocks.length === 0 && (
                    <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                        Komponen tidak ditemukan.
                    </p>
                )}
            </div>

            {children && (
                <div className="hidden border-t p-2">{children}</div>
            )}
        </div>
    );
}
