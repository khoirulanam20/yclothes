import { useCallback, useRef } from 'react';
import { driver, type DriveStep, type Driver } from 'driver.js';
import type { AdminTourKey } from '@/lib/admin-tour-keys';
import { markAdminTourComplete, type CompletedTourVariants } from '@/lib/admin-tour-api';
import { filterExistingSteps, getTourStepsForVariant } from '@/lib/admin-tours';
import type { TourPageVariant } from '@/lib/admin-tour-routes';

type StartTourOptions = {
    tourKey: AdminTourKey;
    variant: TourPageVariant;
    force?: boolean;
    onCompleted?: (completedTourVariants: CompletedTourVariants) => void;
};

export function useAdminTourDriver() {
    const driverRef = useRef<Driver | null>(null);
    const isReplayRef = useRef(false);
    const activeTourKeyRef = useRef<AdminTourKey | null>(null);
    const activeVariantRef = useRef<TourPageVariant | null>(null);

    const destroyDriver = useCallback(() => {
        driverRef.current?.destroy();
        driverRef.current = null;
    }, []);

    const startTour = useCallback(({
        tourKey,
        variant,
        force = false,
        onCompleted,
    }: StartTourOptions) => {
        destroyDriver();

        const rawSteps = getTourStepsForVariant(tourKey, variant);
        const steps = filterExistingSteps(rawSteps);

        if (steps.length === 0) {
            return;
        }

        isReplayRef.current = force;
        activeTourKeyRef.current = tourKey;
        activeVariantRef.current = variant;

        const driveSteps: DriveStep[] = steps.map((step) => ({
            element: step.element,
            popover: {
                title: step.title,
                description: step.description,
                side: 'bottom',
                align: 'start',
            },
        }));

        const completeTour = async () => {
            if (isReplayRef.current || !activeTourKeyRef.current || !activeVariantRef.current) {
                return;
            }

            try {
                const completedTourVariants = await markAdminTourComplete(
                    activeTourKeyRef.current,
                    activeVariantRef.current,
                );
                onCompleted?.(completedTourVariants);
            } catch {
                // ignore network errors; tour already shown
            }
        };

        const instance = driver({
            showProgress: true,
            progressText: '{{current}} dari {{total}}',
            nextBtnText: 'Lanjut',
            prevBtnText: 'Kembali',
            doneBtnText: 'Selesai',
            popoverClass: 'admin-tour-popover',
            steps: driveSteps,
            onPopoverRender: (popover) => {
                const footer = popover.footerButtons;
                if (!footer || footer.querySelector('[data-admin-tour-skip]')) {
                    return;
                }

                const skipButton = document.createElement('button');
                skipButton.type = 'button';
                skipButton.className = 'admin-tour-skip-btn';
                skipButton.dataset.adminTourSkip = 'true';
                skipButton.textContent = 'Lewati';
                skipButton.addEventListener('click', () => {
                    instance.destroy();
                });
                footer.prepend(skipButton);
            },
            onDestroyed: () => {
                void completeTour();
                driverRef.current = null;
                activeTourKeyRef.current = null;
                activeVariantRef.current = null;
            },
        });

        driverRef.current = instance;
        instance.drive();
    }, [destroyDriver]);

    return {
        startTour,
        destroyDriver,
    };
}
