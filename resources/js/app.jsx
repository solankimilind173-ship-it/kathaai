import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'react-hot-toast';
import AppErrorBoundary from './Components/AppErrorBoundary';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        if (name.startsWith('Admin/')) {
            const path = name.replace(/^Admin\/?/, '') || 'Dashboard';
            return resolvePageComponent(
                `./Admin/Pages/${path}.jsx`,
                import.meta.glob('./Admin/Pages/**/*.jsx'),
            );
        }
        if (name.startsWith('Project/')) {
            // e.g. 'Project/Pages/Index' -> './Project/Pages/Index.jsx'
            const path = name.replace(/^Project\/Pages\/?/, '') || 'Index';
            return resolvePageComponent(
                `./Project/Pages/${path}.jsx`,
                import.meta.glob('./Project/Pages/**/*.jsx'),
            );
        }
        return resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <AppErrorBoundary>
                <>
                    <Toaster
                        position="top-right"
                        toastOptions={{
                            duration: 4000,
                            className: '!bg-stone-800 !text-white border border-amber-200/30',
                            success: { iconTheme: { primary: '#22c55e' } },
                            error: { iconTheme: { primary: '#ef4444' } },
                        }}
                    />
                    <App {...props} />
                </>
            </AppErrorBoundary>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
