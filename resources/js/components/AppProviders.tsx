import { ThemeProvider } from '@/components/ThemeProvider';
import { AdminConfirmProvider } from '@/components/admin/AdminConfirmProvider';
import { NotificationLayer } from '@/components/NotificationLayer';

export function AppProviders({ children }: { children: React.ReactNode }) {
    return (
        <ThemeProvider>
            <AdminConfirmProvider>
                {children}
                <NotificationLayer />
            </AdminConfirmProvider>
        </ThemeProvider>
    );
}
