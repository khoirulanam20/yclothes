import type { LucideIcon } from 'lucide-react';
import {
    ArrowLeftRight,
    Boxes,
    Building2,
    CreditCard,
    FileText,
    FolderTree,
    Globe,
    HelpCircle,
    History,
    LayoutDashboard,
    LayoutTemplate,
    Megaphone,
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
};

export type AdminNavGroup = {
    label: string;
    icon: LucideIcon;
    collapsible: boolean;
    items: AdminNavItem[];
};

export const adminNavGroups: AdminNavGroup[] = [
    {
        label: 'Dasbor',
        icon: LayoutDashboard,
        collapsible: false,
        items: [{ label: 'Dasbor', href: '/admin', icon: LayoutDashboard }],
    },
    {
        label: 'Penjualan',
        icon: ShoppingBag,
        collapsible: true,
        items: [
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
            {
                label: 'Ulasan',
                href: '/admin/reviews',
                icon: Star,
                permission: ['products.view', 'products.manage'],
            },
        ],
    },
    {
        label: 'Katalog',
        icon: Package,
        collapsible: true,
        items: [
            {
                label: 'Produk',
                href: '/admin/products',
                icon: Package,
                permission: 'products.manage',
            },
            {
                label: 'Kategori',
                href: '/admin/categories',
                icon: FolderTree,
                permission: 'products.manage',
            },
            {
                label: 'Atribut',
                href: '/admin/attributes',
                icon: Tags,
                permission: 'products.manage',
            },
            {
                label: 'Keluarga Atribut',
                href: '/admin/attribute-families',
                icon: Tags,
                permission: 'products.manage',
            },
        ],
    },
    {
        label: 'Tampilan Situs',
        icon: LayoutTemplate,
        collapsible: true,
        items: [
            { label: 'Halaman Utama', href: '/admin/homepage', icon: LayoutTemplate, permission: ['cms.manage', 'settings.manage'] },
            { label: 'Bar Promo', href: '/admin/promo-bar', icon: Megaphone, permission: 'settings.manage' },
            { label: 'Tema & Branding', href: '/admin/theme', icon: Palette, permission: 'settings.manage' },
            { label: 'SEO & Integrasi', href: '/admin/integrations', icon: Globe, permission: 'settings.manage' },
        ],
    },
    {
        label: 'CMS',
        icon: FileText,
        collapsible: true,
        items: [
            { label: 'Halaman', href: '/admin/cms-pages', icon: FileText, permission: 'cms.manage' },
            { label: 'Blog', href: '/admin/blog-posts', icon: Newspaper, permission: 'cms.manage' },
            { label: 'Navigasi', href: '/admin/navigation', icon: Menu, permission: 'cms.manage' },
            { label: 'FAQ', href: '/admin/faq-categories', icon: HelpCircle, permission: 'cms.manage' },
        ],
    },
    {
        label: 'Inventory',
        icon: Boxes,
        collapsible: true,
        items: [
            { label: 'Stok', href: '/admin/inventories', icon: Boxes, permission: 'inventory.manage' },
            { label: 'Gudang', href: '/admin/warehouses', icon: Building2, permission: 'inventory.manage' },
            {
                label: 'Pergerakan Stok',
                href: '/admin/stock-movements',
                icon: ArrowLeftRight,
                permission: 'inventory.manage',
            },
        ],
    },
    {
        label: 'Pajak',
        icon: Receipt,
        collapsible: true,
        items: [
            { label: 'Tarif Pajak', href: '/admin/tax-rates', icon: Receipt, permission: 'settings.manage' },
            { label: 'Zona Pajak', href: '/admin/tax-zones', icon: Receipt, permission: 'settings.manage' },
        ],
    },
    {
        label: 'Promosi',
        icon: Ticket,
        collapsible: true,
        items: [
            { label: 'Aturan Keranjang', href: '/admin/cart-rules', icon: Ticket, permission: 'promotions.manage' },
            { label: 'Aturan Katalog', href: '/admin/catalog-rules', icon: Percent, permission: 'promotions.manage' },
            { label: 'Pop up Promosi', href: '/admin/promotion-popups', icon: Ticket, permission: 'promotions.manage' },
            { label: 'Ongkir', href: '/admin/shipping-costs', icon: Truck, permission: 'settings.manage' },
            { label: 'Rekening', href: '/admin/payment-banks', icon: CreditCard, permission: 'settings.manage' },
        ],
    },
    {
        label: 'Pengaturan',
        icon: Settings,
        collapsible: false,
        items: [{ label: 'Pengaturan', href: '/admin/settings', icon: Settings, permission: 'settings.manage' }],
    },
    {
        label: 'Sistem',
        icon: Shield,
        collapsible: true,
        items: [
            { label: 'Peran', href: '/admin/roles', icon: Shield, permission: 'staff.manage' },
            { label: 'Staff', href: '/admin/staff', icon: Users, permission: 'staff.manage' },
            { label: 'Log Aktivitas', href: '/admin/activity-logs', icon: History, permission: 'staff.manage' },
        ],
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

export function isNavGroupActive(url: string, group: AdminNavGroup): boolean {
    return group.items.some((item) => isNavItemActive(url, item));
}

export function groupNavItems(
    permissions: string[],
    isSuperAdmin: boolean,
): AdminNavGroup[] {
    return adminNavGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => canAccessNav(permissions, isSuperAdmin, item)),
        }))
        .filter((group) => group.items.length > 0);
}
