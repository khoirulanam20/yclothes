import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export type SortOption = { value: string; label: string };

type Props = {
    value: string;
    options: SortOption[];
    onChange: (value: string) => void;
    className?: string;
};

export function ProductSortDropdown({ value, options, onChange, className }: Props) {
    const selected = options.find((opt) => opt.value === value) ?? options[0];

    return (
        <div className={cn('relative inline-flex items-center', className)}>
            <label className="sr-only" htmlFor="product-sort">
                Urutkan produk
            </label>
            <span className="mr-2 text-sm text-muted-foreground whitespace-nowrap">Urutkan:</span>
            <div className="relative">
                <select
                    id="product-sort"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="appearance-none rounded-lg border border-input bg-background pl-3 pr-8 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-muted/40 focus:outline-none focus:ring-2 focus:ring-ring cursor-pointer"
                >
                    {options.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            </div>
            <span className="sr-only">{selected?.label}</span>
        </div>
    );
}
