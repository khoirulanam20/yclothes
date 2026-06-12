export type Theme = {
    brandName: string;
    appName: string;
    brandLogo: string | null;
    faviconUrl: string | null;
    colorGold: string;
    colorAccent: string;
    waNumber: string;
    storeLocation: string;
    siteTitle: string;
    siteDescription: string;
    siteKeywords: string;
    promoBarText: string;
    promoBarEnabled: boolean;
    promoBarCtaLabel: string;
    promoBarBgColor: string | null;
    promoBarTextColor: string | null;
    socialInstagram: string | null;
    socialFacebook: string | null;
    socialTiktok: string | null;
};

export type CategoryNav = {
    id: number;
    name: string;
    slug: string;
    imageUrl: string | null;
    order?: number;
    parentId?: number | null;
    children?: CategoryNav[];
};

export type NavItem = {
    id: number;
    label: string;
    url: string;
    children?: { id: number; label: string; url: string }[];
};

export type PromotionPopupData = {
    id: number;
    title: string;
    imageUrl?: string | null;
    buttonLabel?: string | null;
    buttonUrl?: string | null;
    displayDurationSeconds?: number;
};

export type GdprSettings = {
    enabled: boolean;
    message?: string;
    policyUrl?: string | null;
    cookieLifetimeDays?: number;
};

export type AdminBadges = {
    orders: number;
    returns: number;
    reviews: number;
    lowStock: number;
    notificationsUnread: number;
};

export type SharedPageProps = {
    auth: {
        customer: { id: number; name: string; email: string; emailVerified: boolean } | null;
        admin: {
            id: number;
            name: string;
            email: string;
            isSuperAdmin: boolean;
            permissions: string[];
            completedTourVariants: Record<string, ('index' | 'create' | 'edit' | 'show' | 'special' | 'nested')[]>;
        } | null;
    };
    flash: { success?: string; error?: string };
    cartCount: number;
    theme: Theme;
    navigation: { header: NavItem[]; footer: NavItem[] };
    categories: CategoryNav[];
    promotionPopup?: PromotionPopupData | null;
    gdpr?: GdprSettings;
    adminBadges?: AdminBadges | null;
};

declare module '@inertiajs/core' {
    interface PageProps extends SharedPageProps {}
}
