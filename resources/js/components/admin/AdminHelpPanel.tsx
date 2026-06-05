import { ChevronDown, HelpCircle } from 'lucide-react';
import { useState } from 'react';
import type { HelpSection } from '@/lib/admin-help-content';
import { cn } from '@/lib/utils';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    section: HelpSection;
    defaultOpen?: boolean;
    className?: string;
};

export function AdminHelpPanel({ section, defaultOpen = false, className }: Props) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <Card className={cn('border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/20', className)}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="flex w-full items-center justify-between gap-2 px-4 py-3 text-left"
            >
                <span className="flex items-center gap-2 text-sm font-medium text-blue-900 dark:text-blue-100">
                    <HelpCircle className="size-4 shrink-0" />
                    {section.title}
                </span>
                <ChevronDown className={cn('size-4 shrink-0 transition-transform', open && 'rotate-180')} />
            </button>
            {open && (
                <CardContent className="space-y-3 border-t border-blue-200 px-4 pb-4 pt-3 text-sm text-blue-950 dark:border-blue-900 dark:text-blue-50">
                    {section.paragraphs?.map((p) => (
                        <p key={p}>{p}</p>
                    ))}
                    {section.steps && (
                        <ol className="list-decimal space-y-1 pl-5">
                            {section.steps.map((step) => (
                                <li key={step}>{step}</li>
                            ))}
                        </ol>
                    )}
                    {section.list && (
                        <ul className="list-disc space-y-1 pl-5">
                            {section.list.map((item) => (
                                <li key={item}>{item}</li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            )}
        </Card>
    );
}
