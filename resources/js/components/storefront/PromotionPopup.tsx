import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import type { SharedPageProps } from '@/types';

function dismissKey(popupId: number): string {
    return `popup_manual_dismissed_${popupId}`;
}

export function PromotionPopup() {
    const { promotionPopup } = usePage<SharedPageProps>().props;
    const [open, setOpen] = useState(false);
    const isAutoClosing = useRef(false);

    useEffect(() => {
        if (!promotionPopup) return;
        if (sessionStorage.getItem(dismissKey(promotionPopup.id))) return;
        setOpen(true);

        if (promotionPopup.displayDurationSeconds && promotionPopup.displayDurationSeconds > 0) {
            const timer = setTimeout(() => {
                isAutoClosing.current = true;
                setOpen(false);
            }, promotionPopup.displayDurationSeconds * 1000);
            return () => clearTimeout(timer);
        }
    }, [promotionPopup]);

    if (!promotionPopup) return null;

    const dismissPermanently = () => {
        sessionStorage.setItem(dismissKey(promotionPopup.id), '1');
        setOpen(false);
    };

    const handleOpenChange = (nextOpen: boolean) => {
        if (nextOpen) {
            setOpen(true);
            return;
        }
        if (isAutoClosing.current) {
            isAutoClosing.current = false;
            setOpen(false);
            return;
        }
        dismissPermanently();
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent
                className={[
                    'max-w-md gap-0 overflow-hidden border-0 p-0 sm:rounded-xl',
                    '[&>button]:absolute [&>button]:right-3 [&>button]:top-3 [&>button]:z-10',
                    '[&>button]:flex [&>button]:h-8 [&>button]:w-8 [&>button]:items-center [&>button]:justify-center',
                    '[&>button]:rounded-full [&>button]:bg-black/60 [&>button]:text-white [&>button]:opacity-100',
                    '[&>button]:ring-0 [&>button]:ring-offset-0 [&>button]:transition-colors',
                    '[&>button]:hover:bg-black/80 [&>button]:hover:opacity-100',
                    '[&>button]:focus:ring-2 [&>button]:focus:ring-white/50',
                ].join(' ')}
            >
                {promotionPopup.imageUrl ? (
                    <div className="relative">
                        <img src={promotionPopup.imageUrl} alt={promotionPopup.title} className="w-full aspect-[4/3] object-cover" />
                    </div>
                ) : null}
                <div className="space-y-3 bg-background p-4">
                    <h3 className="pr-8 text-lg font-semibold">{promotionPopup.title}</h3>
                    {promotionPopup.buttonLabel && promotionPopup.buttonUrl && (
                        <Button asChild className="w-full" onClick={dismissPermanently}>
                            <Link href={promotionPopup.buttonUrl}>{promotionPopup.buttonLabel}</Link>
                        </Button>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
