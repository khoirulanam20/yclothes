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
        <div className={cn('relative flex min-w-0 items-center', className)}>
            <label className="sr-only" htmlFor="product-sort">
                Urutkan produk
            </label>
            <span className="mr-2 hidden text-sm text-muted-foreground whitespace-nowrap sm:inline">Urutkan:</span>
            <div className="relative min-w-0 flex-1 sm:flex-initial">
                <select
                    id="product-sort"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="h-9 w-full min-w-0 appearance-none rounded-lg border border-input bg-background py-2 pl-3 pr-8 text-sm font-medium shadow-sm transition-colors hover:bg-muted/40 focus:outline-none focus:ring-2 focus:ring-ring cursor-pointer sm:w-auto"
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
