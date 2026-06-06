import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { AppProviders } from '@/components/AppProviders';

function getAppName(): string {
    return (
        document.querySelector('meta[name="app-name"]')?.getAttribute('content')?.trim() ||
        import.meta.env.VITE_APP_NAME?.trim() ||
        ''
    );
}

createInertiaApp({
    title: (title) => {
        const appName = getAppName();
        const pageTitle = title?.trim();

        if (!pageTitle || pageTitle === appName) {
            return appName;
        }

        return `${pageTitle} — ${appName}`;
    },
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
