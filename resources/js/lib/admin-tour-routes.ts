import type { AdminTourKey } from '@/lib/admin-tour-keys';
import { hrefToTourKey } from '@/lib/admin-tour-keys';
import { adminNavGroups, canAccessNav, type AdminNavItem } from '@/lib/admin-nav';

export type TourPageVariant = 'index' | 'create' | 'edit' | 'show' | 'special' | 'nested';

const CONFIGURATION_PREFIXES = [
    '/admin/configuration',
    '/admin/homepage',
    '/admin/tax-rates',
    '/admin/tax-zones',
    '/admin/shipping-costs',
    '/admin/payment-banks',
    '/admin/sliders',
    '/admin/appearance',
    '/admin/theme',
    '/admin/integrations',
    '/admin/promo-bar',
];

const FAQ_PREFIXES = ['/admin/faq-categories', '/admin/faq-items'];

const PATH_PREFIX_TO_TOUR_KEY: { prefix: string; key: AdminTourKey }[] = [
    { prefix: '/admin/attribute-families', key: 'attribute-families' },
    { prefix: '/admin/attributes', key: 'attributes' },
    { prefix: '/admin/stock-movements', key: 'stock-movements' },
    { prefix: '/admin/promotion-popups', key: 'promotion-popups' },
    { prefix: '/admin/catalog-rules', key: 'catalog-rules' },
    { prefix: '/admin/activity-logs', key: 'activity-logs' },
    { prefix: '/admin/cart-rules', key: 'cart-rules' },
    { prefix: '/admin/cms-pages', key: 'cms-pages' },
    { prefix: '/admin/blog-posts', key: 'blog-posts' },
    { prefix: '/admin/categories', key: 'categories' },
    { prefix: '/admin/inventories', key: 'inventories' },
    { prefix: '/admin/warehouses', key: 'warehouses' },
    { prefix: '/admin/navigation', key: 'navigation' },
    { prefix: '/admin/products', key: 'products' },
    { prefix: '/admin/orders', key: 'orders' },
    { prefix: '/admin/returns', key: 'returns' },
    { prefix: '/admin/reviews', key: 'reviews' },
    { prefix: '/admin/settings', key: 'settings' },
    { prefix: '/admin/roles', key: 'roles' },
    { prefix: '/admin/staff', key: 'staff' },
];

function normalizePath(url: string): string {
    const path = url.split('?')[0].replace(/\/$/, '');
    return path || '/admin';
}

function resolveTourKey(path: string): AdminTourKey | null {
    if (path === '/admin') {
        return 'dashboard';
    }

    if (CONFIGURATION_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`))) {
        return 'configuration';
    }

    if (FAQ_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`))) {
        return 'faq';
    }

    for (const { prefix, key } of PATH_PREFIX_TO_TOUR_KEY) {
        if (path === prefix || path.startsWith(`${prefix}/`)) {
            return key;
        }
    }

    return null;
}

export function resolvePageVariant(path: string): TourPageVariant {
    if (path === '/admin/homepage') {
        return 'special';
    }

    if (path.startsWith('/admin/configuration/')) {
        return 'edit';
    }

    if (/^\/admin\/faq-categories\/\d+\/items$/.test(path)) {
        return 'nested';
    }

    if (path.endsWith('/create')) {
        return 'create';
    }

    if (/\/\d+\/edit$/.test(path)) {
        return 'edit';
    }

    if (
        path.includes('/builder')
        || path.endsWith('/adjustment')
        || path.endsWith('/transfer')
        || path.endsWith('/policy')
    ) {
        return 'special';
    }

    const showMatch = path.match(/^\/admin\/(orders|returns)\/(\d+)$/);
    if (showMatch) {
        return 'show';
    }

    return 'index';
}

export function resolveAdminTour(url: string): { tourKey: AdminTourKey; variant: TourPageVariant } | null {
    const path = normalizePath(url);

    if (path === '/admin/login') {
        return null;
    }

    const tourKey = resolveTourKey(path);
    if (!tourKey) {
        return null;
    }

    return {
        tourKey,
        variant: resolvePageVariant(path),
    };
}

export function getNavItemForTourKey(tourKey: AdminTourKey): AdminNavItem | null {
    for (const group of adminNavGroups) {
        for (const item of group.items) {
            if (hrefToTourKey(item.href) === tourKey) {
                return item;
            }
        }
    }

    return null;
}

export function canAccessTour(
    tourKey: AdminTourKey,
    permissions: string[],
    isSuperAdmin: boolean,
): boolean {
    const item = getNavItemForTourKey(tourKey);
    if (!item) {
        return true;
    }

    return canAccessNav(permissions, isSuperAdmin, item);
}
