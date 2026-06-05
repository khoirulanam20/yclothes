import { Toaster } from 'sonner';

export function GuestToaster() {
    return (
        <Toaster
            position="top-right"
            closeButton
            richColors
            duration={4500}
            toastOptions={{
                classNames: {
                    toast: 'z-[100]',
                },
            }}
        />
    );
}
