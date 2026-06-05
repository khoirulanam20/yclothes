import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { AppProviders } from '@/components/AppProviders';

const appName = import.meta.env.VITE_APP_NAME || 'YClothes';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup({ el, App, props }) {
        createRoot(el).render(
            <App {...props}>
                {({ Component, props: pageProps, key }) => (
                    <AppProviders>
                        <Component key={key} {...pageProps} />
                    </AppProviders>
                )}
            </App>,
        );
    },
    progress: {
        color: '#C2A56D',
    },
});
