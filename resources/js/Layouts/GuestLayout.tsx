import { PropsWithChildren } from 'react';
import { PromoBar } from '@/components/storefront/PromoBar';
import { PromotionPopup } from '@/components/storefront/PromotionPopup';
import { SiteHeader } from '@/components/storefront/SiteHeader';
import { SiteFooter } from '@/components/storefront/SiteFooter';
import { CookieConsentBanner } from '@/components/storefront/CookieConsentBanner';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex flex-col bg-page-background">
            <PromoBar />
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
            <PromotionPopup />
            <CookieConsentBanner />
        </div>
    );
}
