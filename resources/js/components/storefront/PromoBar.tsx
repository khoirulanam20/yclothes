import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types';

export function PromoBar() {
    const { theme } = usePage<SharedPageProps>().props;

    return (
        <div className="bg-promo-background text-promo-foreground text-center text-xs py-2 px-4">
            <div className="container mx-auto flex justify-between items-center gap-2">
                <span className="truncate">{theme.storeLocation}</span>
                <span className="hidden sm:inline truncate">{theme.promoBarText}</span>
                <a href={`https://wa.me/${theme.waNumber}`} className="shrink-0 hover:underline font-medium">
                    Hubungi WA
                </a>
            </div>
        </div>
    );
}
