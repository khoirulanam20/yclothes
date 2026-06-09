import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { shouldHideMobileNav } from '@/lib/mobile-nav';
import { cn } from '@/lib/utils';
import type { GdprSettings, SharedPageProps } from '@/types';

const CONSENT_KEY = 'yclothes_cookie_consent';

export function CookieConsentBanner() {
    const { props, url } = usePage<SharedPageProps>();
    const { gdpr } = props;
    const [visible, setVisible] = useState(false);
    const showMobileNav = !shouldHideMobileNav(url);

    useEffect(() => {
        if (!gdpr?.enabled) {
            return;
        }

        const stored = localStorage.getItem(CONSENT_KEY);
        if (!stored) {
            setVisible(true);
        }
    }, [gdpr?.enabled]);

    if (!gdpr?.enabled || !visible) {
        return null;
    }

    const settings = gdpr as GdprSettings;

    const accept = () => {
        localStorage.setItem(CONSENT_KEY, '1');
        const days = settings.cookieLifetimeDays ?? 365;
        document.cookie = `${CONSENT_KEY}=1; path=/; max-age=${days * 86400}; SameSite=Lax`;
        setVisible(false);
    };

    return (
        <div
            className={cn(
                'fixed inset-x-0 z-50 p-4',
                showMobileNav
                    ? 'bottom-[calc(4rem+env(safe-area-inset-bottom,0px))] md:bottom-0'
                    : 'bottom-0',
            )}
        >
            <div className="mx-auto max-w-3xl rounded-lg border bg-card p-4 shadow-lg flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <p className="text-sm text-muted-foreground">
                    {settings.message}
                    {settings.policyUrl && (
                        <>
                            {' '}
                            <Link href={settings.policyUrl} className="underline text-primary">
                                Pelajari lebih lanjut
                            </Link>
                        </>
                    )}
                </p>
                <Button size="sm" onClick={accept} className="shrink-0">
                    Setuju
                </Button>
            </div>
        </div>
    );
}
