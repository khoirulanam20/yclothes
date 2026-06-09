import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { MobileMenuDrawer } from '@/components/storefront/MobileMenuDrawer';
import { cn } from '@/lib/utils';
import {
    bottomNavItems,
    isNavActive,
    resolveAccountHref,
    resolveAccountLabel,
    shouldHideMobileNav,
} from '@/lib/mobile-nav';
import type { SharedPageProps } from '@/types';

export function MobileBottomNav() {
    const { url, props } = usePage<SharedPageProps>();
    const { cartCount, auth } = props;
    const [menuOpen, setMenuOpen] = useState(false);

    if (shouldHideMobileNav(url)) {
        return null;
    }

    const isLoggedIn = Boolean(auth.customer);
    const accountHref = resolveAccountHref(isLoggedIn);
    const accountLabel = resolveAccountLabel(isLoggedIn, auth.customer?.name);

    return (
        <>
            <nav
                className="mobile-bottom-nav fixed inset-x-0 bottom-0 z-40 border-t bg-header-background shadow-[0_-2px_10px_rgba(0,0,0,0.06)] md:hidden"
                aria-label="Navigasi utama"
            >
                <div className="mx-auto flex h-16 max-w-lg items-stretch justify-around px-1">
                    {bottomNavItems.map((item) => {
                        const Icon = item.icon;
                        const isMenu = item.isMenuTrigger;
                        const href = item.id === 'account' ? accountHref : item.href;
                        const label = item.id === 'account' ? accountLabel : item.label;
                        const active = !isMenu && isNavActive(item.id, url);

                        const content = (
                            <>
                                {active && (
                                    <span className="absolute top-1 h-0.5 w-5 rounded-full bg-primary" aria-hidden />
                                )}
                                <span className="relative flex flex-col items-center justify-center gap-0.5">
                                    <Icon
                                        className={cn('size-5', active ? 'text-primary' : 'text-muted-foreground')}
                                        strokeWidth={active ? 2.25 : 2}
                                    />
                                    {item.id === 'cart' && cartCount > 0 && (
                                        <span className="absolute -top-1.5 -right-2.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-0.5 text-[10px] font-bold text-primary-foreground">
                                            {cartCount > 99 ? '99+' : cartCount}
                                        </span>
                                    )}
                                </span>
                                <span
                                    className={cn(
                                        'max-w-[4.5rem] truncate text-[10px] leading-tight',
                                        active ? 'font-medium text-primary' : 'text-muted-foreground',
                                    )}
                                >
                                    {label}
                                </span>
                            </>
                        );

                        if (isMenu) {
                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => setMenuOpen(true)}
                                    className="relative flex min-w-11 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-1 text-muted-foreground transition-colors hover:text-primary"
                                    aria-label="Buka menu"
                                    aria-expanded={menuOpen}
                                >
                                    {content}
                                </button>
                            );
                        }

                        return (
                            <Link
                                key={item.id}
                                href={href!}
                                className={cn(
                                    'relative flex min-w-11 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-1 transition-colors',
                                    active ? 'text-primary' : 'text-muted-foreground hover:text-primary',
                                )}
                            >
                                {content}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <MobileMenuDrawer open={menuOpen} onOpenChange={setMenuOpen} />
        </>
    );
}
