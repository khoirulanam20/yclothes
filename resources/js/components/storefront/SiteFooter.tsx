import { Link, usePage } from '@inertiajs/react';
import { resolveNav } from '@/lib/storefront-nav';
import type { SharedPageProps } from '@/types';

export function SiteFooter() {
    const { theme, navigation } = usePage<SharedPageProps>().props;
    const navItems = resolveNav(navigation.header, navigation.footer);
    const socials = [
        { label: 'Instagram', url: theme.socialInstagram },
        { label: 'Facebook', url: theme.socialFacebook },
        { label: 'TikTok', url: theme.socialTiktok },
    ].filter((s) => s.url);

    return (
        <footer className="bg-card border-t mt-auto">
            <nav className="border-b">
                <div className="container mx-auto flex flex-wrap justify-center gap-x-6 gap-y-2 px-4 py-3">
                    {navItems.map((item) => (
                        <Link
                            key={item.id}
                            href={item.url}
                            className="text-sm font-medium text-muted-foreground hover:text-primary transition-colors whitespace-nowrap"
                        >
                            {item.label}
                        </Link>
                    ))}
                </div>
            </nav>

            <div className="container mx-auto px-4 py-10 grid md:grid-cols-3 gap-8">
                <div>
                    <p className="text-lg font-bold text-primary mb-2">{theme.brandName}</p>
                    <p className="text-sm text-muted-foreground">{theme.siteDescription}</p>
                    {socials.length > 0 && (
                        <div className="flex gap-3 mt-4">
                            {socials.map((s) => (
                                <a
                                    key={s.label}
                                    href={s.url!}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm text-muted-foreground hover:text-primary transition-colors"
                                >
                                    {s.label}
                                </a>
                            ))}
                        </div>
                    )}
                </div>
                <div>
                    <p className="font-semibold mb-3">Menu</p>
                    <ul className="space-y-2 text-sm text-muted-foreground">
                        {navItems.map((item) => (
                            <li key={item.id}>
                                <Link href={item.url} className="hover:text-primary transition-colors">
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
                <div>
                    <p className="font-semibold mb-3">Hubungi Kami</p>
                    <p className="text-sm text-muted-foreground mb-3">{theme.storeLocation}</p>
                    <a
                        href={`https://wa.me/${theme.waNumber}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 bg-primary text-primary-foreground px-4 py-2 rounded-full text-sm font-medium hover:opacity-90 transition-opacity"
                    >
                        WhatsApp
                    </a>
                </div>
            </div>
            <div className="border-t py-4 text-center text-xs text-muted-foreground">
                © {new Date().getFullYear()} {theme.brandName}. All rights reserved.
            </div>
        </footer>
    );
}
