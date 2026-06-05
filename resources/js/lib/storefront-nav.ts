import type { NavItem } from '@/types';

export const defaultNav: NavItem[] = [
    { id: 1, label: 'Beranda', url: '/' },
    { id: 2, label: 'Produk', url: '/products' },
    { id: 3, label: 'Tentang Kami', url: '/page/tentang-kami' },
    { id: 4, label: 'Cara Belanja', url: '/page/cara-belanja' },
    { id: 5, label: 'Lacak Pesanan', url: '/order/track' },
];

export function resolveNav(header: NavItem[], footer: NavItem[]): NavItem[] {
    if (header.length) {
        return header;
    }
    if (footer.length) {
        return footer;
    }

    return defaultNav;
}
