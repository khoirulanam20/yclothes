import type { LucideIcon } from 'lucide-react';
import { Home, Menu, ShoppingBag, ShoppingCart, User } from 'lucide-react';

export const MOBILE_NAV_HEIGHT = '4rem';

export type BottomNavItemId = 'home' | 'products' | 'cart' | 'account' | 'menu';

export type BottomNavItem = {
    id: BottomNavItemId;
    label: string;
    href?: string;
    icon: LucideIcon;
    isMenuTrigger?: boolean;
};

export const bottomNavItems: BottomNavItem[] = [
    { id: 'home', label: 'Beranda', href: '/', icon: Home },
    { id: 'products', label: 'Produk', href: '/products', icon: ShoppingBag },
    { id: 'cart', label: 'Keranjang', href: '/cart', icon: ShoppingCart },
    { id: 'account', label: 'Akun', href: '/account/profile', icon: User },
    { id: 'menu', label: 'Menu', icon: Menu, isMenuTrigger: true },
];

const bottomNavUrls = new Set(['/', '/products', '/cart', '/account/profile', '/account/login']);

export function resolveAccountHref(isLoggedIn: boolean): string {
    return isLoggedIn ? '/account/profile' : '/account/login';
}

export function resolveAccountLabel(isLoggedIn: boolean, customerName?: string): string {
    if (!isLoggedIn) {
        return 'Masuk';
    }

    if (customerName) {
        const first = customerName.split(' ')[0];
        return first.length > 8 ? `${first.slice(0, 7)}…` : first;
    }

    return 'Akun';
}

export function isNavActive(itemId: BottomNavItemId, url: string): boolean {
    const path = url.split('?')[0];

    switch (itemId) {
        case 'home':
            return path === '/';
        case 'products':
            return path === '/products' || path.startsWith('/products/');
        case 'cart':
            return path.startsWith('/cart');
        case 'account':
            return path.startsWith('/account');
        case 'menu':
            return false;
        default:
            return false;
    }
}

export function shouldHideMobileNav(url: string): boolean {
    const path = url.split('?')[0];

    if (path === '/checkout' || path.startsWith('/checkout/')) {
        return true;
    }

    if (path.startsWith('/order/') && path !== '/order/track') {
        return true;
    }

    return false;
}

export function isBottomNavPrimaryUrl(url: string): boolean {
    const path = url.split('?')[0];

    if (bottomNavUrls.has(path)) {
        return true;
    }

    if (path.startsWith('/account/')) {
        return true;
    }

    return false;
}

export function getDrawerNavItems(
    headerItems: { id: number; label: string; url: string }[],
    footerItems: { id: number; label: string; url: string }[],
): { id: number; label: string; url: string }[] {
    const seen = new Set<string>();
    const items: { id: number; label: string; url: string }[] = [];

    for (const item of [...headerItems, ...footerItems]) {
        const path = item.url.split('?')[0];
        if (isBottomNavPrimaryUrl(path)) {
            continue;
        }
        if (seen.has(path)) {
            continue;
        }
        seen.add(path);
        items.push(item);
    }

    return items;
}
