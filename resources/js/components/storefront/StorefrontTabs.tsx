import { cn } from '@/lib/utils';

export type StorefrontTab = { id: string; label: string };

type Props = {
    tabs: StorefrontTab[];
    activeTab: string;
    onChange: (id: string) => void;
    className?: string;
};

export function StorefrontTabs({ tabs, activeTab, onChange, className }: Props) {
    return (
        <div className={cn('border-b', className)}>
            <nav className="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                {tabs.map((tab) => {
                    const active = tab.id === activeTab;

                    return (
                        <button
                            key={tab.id}
                            type="button"
                            onClick={() => onChange(tab.id)}
                            className={cn(
                                'whitespace-nowrap border-b-2 pb-3 pt-1 text-sm font-medium transition-colors',
                                active
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            )}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </nav>
        </div>
    );
}
