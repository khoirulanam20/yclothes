import { usePage } from '@inertiajs/react';
import { PropsWithChildren, useEffect } from 'react';
import { PromoBar } from '@/components/storefront/PromoBar';
import { PromotionPopup } from '@/components/storefront/PromotionPopup';
import { SiteHeader } from '@/components/storefront/SiteHeader';
import { SiteFooter } from '@/components/storefront/SiteFooter';
import { CookieConsentBanner } from '@/components/storefront/CookieConsentBanner';
import { MobileBottomNav } from '@/components/storefront/MobileBottomNav';
import { shouldHideMobileNav } from '@/lib/mobile-nav';
import { cn } from '@/lib/utils';

export default function GuestLayout({ children }: PropsWithChildren) {
    const { url } = usePage();
    const showMobileNav = !shouldHideMobileNav(url);
    const hasPageBottomBar = url.split('?')[0] === '/checkout';

    useEffect(() => {
        document.body.classList.toggle('has-mobile-bottom-nav', showMobileNav);
        document.body.classList.toggle('has-page-bottom-bar', hasPageBottomBar);
        document.body.classList.add('is-guest-storefront');

        return () => {
            document.body.classList.remove('has-mobile-bottom-nav');
            document.body.classList.remove('has-page-bottom-bar');
            document.body.classList.remove('is-guest-storefront');
        };
    }, [showMobileNav, hasPageBottomBar]);

    return (
        <div className="min-h-screen flex flex-col bg-page-background">
            <PromoBar />
            <SiteHeader />
            <main
                className={cn(
                    'flex-1',
                    showMobileNav && 'pb-[calc(4rem+env(safe-area-inset-bottom,0px))] md:pb-0',
                )}
            >
                {children}
            </main>
            <SiteFooter />
            <MobileBottomNav />
            <PromotionPopup />
            <CookieConsentBanner />
        </div>
    );
}
