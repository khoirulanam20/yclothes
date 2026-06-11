import { Link, usePage } from '@inertiajs/react';
import { MapPin, MessageCircle } from 'lucide-react';
import { resolveNav } from '@/lib/storefront-nav';
import type { NavItem, SharedPageProps } from '@/types';

function SocialIcon({ label }: { label: string }) {
    const icons: Record<string, string> = {
        Instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
        Facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        TikTok: 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.58-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72 2.16.41 3.99 1.86 5.02 3.85.11.19.2.39.29.59-.01-1.25-.01-2.5-.01-3.75-.01-.63 0-1.26-.02-1.89z',
    };

    const path = icons[label];
    if (!path) {
        return null;
    }

    return (
        <svg viewBox="0 0 24 24" className="size-4 fill-current" aria-hidden>
            <path d={path} />
        </svg>
    );
}

const defaultHelpLinks: NavItem[] = [
    { id: -1, label: 'Lacak Pesanan', url: '/order/track' },
    { id: -2, label: 'FAQ', url: '/faq' },
    { id: -3, label: 'Cara Belanja', url: '/page/cara-belanja' },
];

function mergeLinks(primary: NavItem[], fallback: NavItem[]): NavItem[] {
    const seen = new Set<string>();
    const merged: NavItem[] = [];

    for (const item of [...primary, ...fallback]) {
        const key = item.url.toLowerCase();
        if (seen.has(key)) {
            continue;
        }
        seen.add(key);
        merged.push(item);
    }

    return merged;
}

function FooterLinkList({ items }: { items: NavItem[] }) {
    return (
        <ul className="space-y-2.5">
            {items.map((item) => (
                <li key={`${item.id}-${item.url}`}>
                    <Link
                        href={item.url}
                        className="text-sm text-muted-foreground transition-colors hover:text-primary"
                    >
                        {item.label}
                    </Link>
                </li>
            ))}
        </ul>
    );
}

export function SiteFooter() {
    const { theme, navigation } = usePage<SharedPageProps>().props;
    const menuLinks = navigation.footer.length > 0
        ? navigation.footer
        : resolveNav(navigation.header, navigation.footer);
    const helpFromNav = menuLinks.filter((item) =>
        /lacak|faq|bantuan|kontak|help|track|belanja|cara/i.test(item.label),
    );
    const helpLinks = mergeLinks(helpFromNav, defaultHelpLinks);
    const menuOnlyLinks = menuLinks.filter(
        (item) => !helpLinks.some((help) => help.url.toLowerCase() === item.url.toLowerCase()),
    );

    const socials = [
        { label: 'Instagram', url: theme.socialInstagram },
        { label: 'Facebook', url: theme.socialFacebook },
        { label: 'TikTok', url: theme.socialTiktok },
    ].filter((s) => s.url);

    return (
        <footer className="mt-auto border-t bg-muted/30 pb-[calc(4rem+env(safe-area-inset-bottom,0px))] md:pb-0">
            <div className="container mx-auto px-4 py-10 md:py-12">
                <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-12 lg:gap-10">
                    <div className="sm:col-span-2 lg:col-span-5">
                        <Link href="/" className="inline-flex items-center gap-2">
                            {theme.brandLogo ? (
                                <img src={theme.brandLogo} alt={theme.brandName} className="h-9 w-auto" />
                            ) : (
                                <span className="text-xl font-bold text-primary">{theme.brandName}</span>
                            )}
                        </Link>
                        {theme.siteDescription && (
                            <p className="mt-3 max-w-md text-sm leading-relaxed text-muted-foreground">
                                {theme.siteDescription}
                            </p>
                        )}
                        {socials.length > 0 && (
                            <div className="mt-5 flex gap-2.5">
                                {socials.map((s) => (
                                    <a
                                        key={s.label}
                                        href={s.url!}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex size-9 items-center justify-center rounded-full border border-border bg-background text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                                        aria-label={s.label}
                                    >
                                        <SocialIcon label={s.label} />
                                    </a>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="lg:col-span-3">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-foreground">Menu</p>
                        <FooterLinkList items={menuOnlyLinks.length > 0 ? menuOnlyLinks : menuLinks} />
                    </div>

                    <div className="lg:col-span-2">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-foreground">Bantuan</p>
                        <FooterLinkList items={helpLinks} />
                    </div>

                    <div className="lg:col-span-2">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-foreground">Hubungi Kami</p>
                        <div className="space-y-3">
                            {theme.storeLocation && (
                                <p className="flex items-start gap-2 text-sm leading-relaxed text-muted-foreground">
                                    <MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
                                    <span>{theme.storeLocation}</span>
                                </p>
                            )}
                            {theme.waNumber && (
                                <a
                                    href={`https://wa.me/${theme.waNumber}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 sm:w-auto"
                                >
                                    <MessageCircle className="size-4" />
                                    Chat WhatsApp
                                </a>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="border-t border-border/60 bg-background py-4">
                <p className="container mx-auto px-4 text-center text-xs text-muted-foreground">
                    © {new Date().getFullYear()} {theme.brandName}. Hak cipta dilindungi.
                </p>
            </div>
        </footer>
    );
}
