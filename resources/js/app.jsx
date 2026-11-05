import '../css/app.css';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';

import { registerNetworkErrorHandler } from '@/lib/network-error';
import { createAppTheme } from '@/theme';
import { createInertiaApp, router } from '@inertiajs/react';
import CssBaseline from '@mui/material/CssBaseline';
import { ThemeProvider } from '@mui/material/styles';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = document.documentElement.dataset.appName;
const { props: initialProps } = JSON.parse(
    document.getElementById('app').dataset.page,
);
const theme = createAppTheme(initialProps.branding);

registerNetworkErrorHandler(router);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ThemeProvider theme={theme}>
                <CssBaseline />
                <App {...props} />
            </ThemeProvider>,
        );
    },
    progress: {
        color: theme.palette.primary.main,
    },
});
