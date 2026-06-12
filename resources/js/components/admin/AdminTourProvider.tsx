import { usePage } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
    type PropsWithChildren,
} from 'react';
import { useAdminTourDriver } from '@/hooks/useAdminTour';
import type { CompletedTourVariants } from '@/lib/admin-tour-api';
import type { AdminTourKey } from '@/lib/admin-tour-keys';
import { canAccessTour, resolveAdminTour } from '@/lib/admin-tour-routes';
import type { SharedPageProps } from '@/types';

type AdminTourContextValue = {
    completedTourVariants: CompletedTourVariants;
    currentTourKey: AdminTourKey | null;
    replayTour: () => void;
};

const AdminTourContext = createContext<AdminTourContextValue | null>(null);

export function useAdminTourContext(): AdminTourContextValue {
    const context = useContext(AdminTourContext);
    if (!context) {
        throw new Error('useAdminTourContext must be used within AdminTourProvider');
    }

    return context;
}

export function AdminTourProvider({ children }: PropsWithChildren) {
    const { url, props } = usePage<SharedPageProps>();
    const admin = props.auth.admin;
    const [completedTourVariants, setCompletedTourVariants] = useState<CompletedTourVariants>(
        admin?.completedTourVariants ?? {},
    );
    const { startTour, destroyDriver } = useAdminTourDriver();
    const timeoutRef = useRef<number | null>(null);

    useEffect(() => {
        setCompletedTourVariants(admin?.completedTourVariants ?? {});
    }, [admin?.completedTourVariants]);

    const resolved = useMemo(() => resolveAdminTour(url), [url]);
    const currentTourKey = resolved?.tourKey ?? null;

    const handleCompleted = useCallback((variants: CompletedTourVariants) => {
        setCompletedTourVariants(variants);
    }, []);

    const runTour = useCallback((force = false) => {
        if (!admin || !resolved) {
            return;
        }

        if (!canAccessTour(resolved.tourKey, admin.permissions, admin.isSuperAdmin)) {
            return;
        }

        const completedForMenu = completedTourVariants[resolved.tourKey] ?? [];
        if (!force && completedForMenu.includes(resolved.variant)) {
            return;
        }

        startTour({
            tourKey: resolved.tourKey,
            variant: resolved.variant,
            force,
            onCompleted: handleCompleted,
        });
    }, [admin, completedTourVariants, handleCompleted, resolved, startTour]);

    const replayTour = useCallback(() => {
        runTour(true);
    }, [runTour]);

    useEffect(() => {
        destroyDriver();

        if (timeoutRef.current) {
            window.clearTimeout(timeoutRef.current);
        }

        if (!admin || !resolved) {
            return;
        }

        timeoutRef.current = window.setTimeout(() => {
            runTour(false);
        }, 500);

        return () => {
            if (timeoutRef.current) {
                window.clearTimeout(timeoutRef.current);
            }
            destroyDriver();
        };
    }, [admin, destroyDriver, resolved, runTour, url]);

    const value = useMemo(() => ({
        completedTourVariants,
        currentTourKey,
        replayTour,
    }), [completedTourVariants, currentTourKey, replayTour]);

    return (
        <AdminTourContext.Provider value={value}>
            {children}
        </AdminTourContext.Provider>
    );
}
