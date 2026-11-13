import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';

import AppShell from '@/Layouts/AppShell';
import { registerNetworkErrorHandler } from '@/lib/network-error';
import { resolvePage } from '@/pages';
import { createAppTheme } from '@/theme';
import { createInertiaApp, router } from '@inertiajs/react';
import CssBaseline from '@mui/material/CssBaseline';
import { ThemeProvider } from '@mui/material/styles';
import { createRoot } from 'react-dom/client';

const appName = document.documentElement.dataset.appName;
const { props: initialProps } = JSON.parse(
    document.getElementById('app').dataset.page,
);
const theme = createAppTheme(initialProps.branding);
const scheme = document.documentElement.dataset.colorScheme ?? 'light';

const withShell = (page) => <AppShell>{page}</AppShell>;

registerNetworkErrorHandler(router);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePage(name, { layout: withShell }),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <ThemeProvider theme={theme} defaultMode="system" noSsr>
                <CssBaseline enableColorScheme />
                <App {...props} />
            </ThemeProvider>,
        );
    },
    progress: {
        color: theme.colorSchemes[scheme].palette.primary.main,
    },
});
