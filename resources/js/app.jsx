import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Admin module: Admin/Dashboard -> Admin/Pages/Dashboard.jsx, Admin/Users/Index -> Admin/Pages/Users/Index.jsx
        if (name.startsWith('Admin/')) {
            const path = name.replace(/^Admin\/?/, '') || 'Dashboard';
            return resolvePageComponent(
                `./Admin/Pages/${path}.jsx`,
                import.meta.glob('./Admin/Pages/**/*.jsx'),
            );
        }
        return resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
