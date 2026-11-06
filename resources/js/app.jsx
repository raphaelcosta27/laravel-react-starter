import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';

import AppShell from '@/Layouts/AppShell';
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
const scheme = document.documentElement.dataset.colorScheme ?? 'light';

/** Estas telas existem sem sessão, ou antes de a conta estar liberada. */
const OUTSIDE_SHELL = /^(Auth\/|Install\/|Welcome$|Error$)/;

const withShell = (page) => <AppShell>{page}</AppShell>;

registerNetworkErrorHandler(router);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );

        if (!OUTSIDE_SHELL.test(name)) {
            page.default.layout = withShell;
        }

        return page;
    },
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
