import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import type { Theme } from '@/types';

function getContrastForeground(hex: string): string {
    const normalized = hex.replace('#', '');
    if (normalized.length !== 6) {
        return '#ffffff';
    }
    const r = parseInt(normalized.slice(0, 2), 16);
    const g = parseInt(normalized.slice(2, 4), 16);
    const b = parseInt(normalized.slice(4, 6), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.55 ? '#1a1a1a' : '#ffffff';
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
    const { theme } = usePage().props as { theme: Theme };

    useEffect(() => {
        const root = document.documentElement;
        if (theme?.colorGold) {
            const foreground = getContrastForeground(theme.colorGold);
            root.style.setProperty('--primary', theme.colorGold);
            root.style.setProperty('--ring', theme.colorGold);
            root.style.setProperty('--promo-background', theme.colorGold);
            root.style.setProperty('--primary-foreground', foreground);
            root.style.setProperty('--promo-foreground', foreground);
        }
        if (theme?.colorAccent) {
            root.style.setProperty('--accent', theme.colorAccent);
        }
        if (theme?.faviconUrl) {
            let link = document.querySelector<HTMLLinkElement>("link[rel='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            link.href = theme.faviconUrl;
        }
    }, [theme]);

    return <>{children}</>;
}
