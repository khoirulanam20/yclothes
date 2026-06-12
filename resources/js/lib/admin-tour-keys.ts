export const ADMIN_TOUR_KEYS = [
    'dashboard',
    'orders',
    'returns',
    'reviews',
    'products',
    'categories',
    'attributes',
    'attribute-families',
    'cms-pages',
    'blog-posts',
    'navigation',
    'faq',
    'inventories',
    'warehouses',
    'stock-movements',
    'cart-rules',
    'catalog-rules',
    'promotion-popups',
    'configuration',
    'settings',
    'roles',
    'staff',
    'activity-logs',
] as const;

export type AdminTourKey = (typeof ADMIN_TOUR_KEYS)[number];

const HREF_TO_TOUR_KEY: Record<string, AdminTourKey> = {
    '/admin': 'dashboard',
    '/admin/orders': 'orders',
    '/admin/returns': 'returns',
    '/admin/reviews': 'reviews',
    '/admin/products': 'products',
    '/admin/categories': 'categories',
    '/admin/attributes': 'attributes',
    '/admin/attribute-families': 'attribute-families',
    '/admin/cms-pages': 'cms-pages',
    '/admin/blog-posts': 'blog-posts',
    '/admin/navigation': 'navigation',
    '/admin/faq-categories': 'faq',
    '/admin/inventories': 'inventories',
    '/admin/warehouses': 'warehouses',
    '/admin/stock-movements': 'stock-movements',
    '/admin/cart-rules': 'cart-rules',
    '/admin/catalog-rules': 'catalog-rules',
    '/admin/promotion-popups': 'promotion-popups',
    '/admin/configuration': 'configuration',
    '/admin/settings': 'settings',
    '/admin/roles': 'roles',
    '/admin/staff': 'staff',
    '/admin/activity-logs': 'activity-logs',
};

export function hrefToTourKey(href: string): AdminTourKey | null {
    return HREF_TO_TOUR_KEY[href] ?? null;
}
