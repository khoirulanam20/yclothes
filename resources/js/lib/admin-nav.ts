import type { LucideIcon } from 'lucide-react';
import {
    ArrowLeftRight,
    Boxes,
    Building2,
    CreditCard,
    FileText,
    FolderTree,
    HelpCircle,
    History,
    Images,
    LayoutDashboard,
    Menu,
    Newspaper,
    Package,
    Palette,
    Percent,
    Receipt,
    Settings,
    Shield,
    ShoppingBag,
    Star,
    Tags,
    Ticket,
    Truck,
    Users,
} from 'lucide-react';

export type AdminNavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    permission?: string | string[];
    section?: string;
};

export type AdminNavGroup = {
    section?: string;
    items: AdminNavItem[];
};

export const adminNavItems: AdminNavItem[] = [
    { label: 'Dasbor', href: '/admin', icon: LayoutDashboard },
    {
        label: 'Pesanan',
        href: '/admin/orders',
        icon: ShoppingBag,
        permission: ['orders.view', 'orders.manage'],
    },
    {
        label: 'Retur',
        href: '/admin/returns',
        icon: ArrowLeftRight,
        permission: 'orders.manage',
    },
    { label: 'Produk', href: '/admin/products', icon: Package, permission: 'products.manage' },
    { label: 'Kategori', href: '/admin/categories', icon: FolderTree, permission: 'products.manage' },
    {
        label: 'Atribut',
        href: '/admin/attribute-families',
        icon: Tags,
        permission: 'products.manage',
    },
    {
        label: 'Ulasan',
        href: '/admin/reviews',
        icon: Star,
        permission: ['products.view', 'products.manage'],
    },
    {
        section: 'CMS',
        label: 'Halaman',
        href: '/admin/cms-pages',
        icon: FileText,
        permission: 'cms.manage',
    },
    { label: 'Blog', href: '/admin/blog-posts', icon: Newspaper, permission: 'cms.manage' },
    { label: 'Slider', href: '/admin/sliders', icon: Images, permission: 'cms.manage' },
    { label: 'Navigasi', href: '/admin/navigation', icon: Menu, permission: 'cms.manage' },
    { label: 'FAQ', href: '/admin/faq-categories', icon: HelpCircle, permission: 'cms.manage' },
    {
        section: 'Inventory',
        label: 'Stok',
        href: '/admin/inventories',
        icon: Boxes,
        permission: 'inventory.manage',
    },
    { label: 'Gudang', href: '/admin/warehouses', icon: Building2, permission: 'inventory.manage' },
    {
        label: 'Pergerakan Stok',
        href: '/admin/stock-movements',
        icon: ArrowLeftRight,
        permission: 'inventory.manage',
    },
    {
        section: 'Pajak',
        label: 'Tarif Pajak',
        href: '/admin/tax-rates',
        icon: Receipt,
        permission: 'settings.manage',
    },
    { label: 'Zona Pajak', href: '/admin/tax-zones', icon: Receipt, permission: 'settings.manage' },
    {
        section: 'Promosi',
        label: 'Aturan Keranjang',
        href: '/admin/cart-rules',
        icon: Ticket,
        permission: 'promotions.manage',
    },
    {
        label: 'Aturan Katalog',
        href: '/admin/catalog-rules',
        icon: Percent,
        permission: 'promotions.manage',
    },
    {
        label: 'Ongkos Kirim',
        href: '/admin/shipping-costs',
        icon: Truck,
        permission: 'settings.manage',
    },
    {
        label: 'Rekening',
        href: '/admin/payment-banks',
        icon: CreditCard,
        permission: 'settings.manage',
    },
    {
        label: 'Tampilan Toko',
        href: '/admin/appearance',
        icon: Palette,
        permission: 'settings.manage',
    },
    { label: 'Pengaturan', href: '/admin/settings', icon: Settings, permission: 'settings.manage' },
    {
        section: 'Sistem',
        label: 'Staff',
        href: '/admin/staff',
        icon: Users,
        permission: 'staff.manage',
    },
    { label: 'Peran', href: '/admin/roles', icon: Shield, permission: 'staff.manage' },
    {
        label: 'Log Aktivitas',
        href: '/admin/activity-logs',
        icon: History,
        permission: 'staff.manage',
    },
];

export function canAccessNav(
    permissions: string[],
    isSuperAdmin: boolean,
    item: AdminNavItem,
): boolean {
    if (!item.permission) return true;
    if (isSuperAdmin || permissions.includes('*')) return true;
    const required = Array.isArray(item.permission) ? item.permission : [item.permission];
    return required.some((p) => permissions.includes(p));
}

export function isNavItemActive(url: string, item: AdminNavItem): boolean {
    const path = url.split('?')[0];
    if (item.href === '/admin') {
        return path === '/admin';
    }
    return path === item.href || path.startsWith(`${item.href}/`);
}

export function groupNavItems(
    permissions: string[],
    isSuperAdmin: boolean,
): AdminNavGroup[] {
    const accessible = adminNavItems.filter((item) =>
        canAccessNav(permissions, isSuperAdmin, item),
    );

    const groups: AdminNavGroup[] = [];
    let currentGroup: AdminNavGroup = { section: undefined, items: [] };
    let lastSection: string | undefined;

    for (const item of accessible) {
        if (item.section) {
            if (currentGroup.items.length > 0) {
                groups.push(currentGroup);
            }
            lastSection = item.section;
            currentGroup = { section: item.section, items: [item] };
        } else if (lastSection) {
            currentGroup.items.push(item);
        } else {
            currentGroup.items.push(item);
        }
    }

    if (currentGroup.items.length > 0) {
        groups.push(currentGroup);
    }

    return groups;
}
