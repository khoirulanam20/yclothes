export type Theme = {
    brandName: string;
    brandLogo: string | null;
    colorGold: string;
    colorAccent: string;
    waNumber: string;
    storeLocation: string;
    siteTitle: string;
    siteDescription: string;
    promoBarText: string;
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

export type SharedPageProps = {
    auth: {
        customer: { id: number; name: string; email: string; emailVerified: boolean } | null;
        admin: { id: number; name: string; email: string; isSuperAdmin: boolean; permissions: string[] } | null;
    };
    flash: { success?: string; error?: string };
    cartCount: number;
    theme: Theme;
    navigation: { header: NavItem[]; footer: NavItem[] };
    categories: CategoryNav[];
};

declare module '@inertiajs/core' {
    interface PageProps extends SharedPageProps {}
}
