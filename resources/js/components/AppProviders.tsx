import { Toaster } from 'sonner';
import { ThemeProvider } from '@/components/ThemeProvider';
import { FlashToaster } from '@/components/FlashToaster';

export function AppProviders({ children }: { children: React.ReactNode }) {
    return (
        <ThemeProvider>
            {children}
            <FlashToaster />
            <Toaster position="top-right" richColors />
        </ThemeProvider>
    );
}
