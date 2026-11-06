import { alpha, createTheme, getContrastRatio } from '@mui/material/styles';

const DEFAULT_PRIMARY = '#3949ab';
const DEFAULT_SECONDARY = '#00695c';

const HEXADECIMAL = /^#[0-9a-f]{6}$/i;
const MINIMUM_CONTRAST = 4.5;
const STEP = 0.05;

const color = (value, fallback) =>
    HEXADECIMAL.test(value ?? '') ? value : fallback;

const channels = (hex) =>
    [1, 3, 5].map((start) => parseInt(hex.slice(start, start + 2), 16));

const mix = (from, to, amount) => {
    const target = channels(to);
    const mixed = channels(from).map((channel, index) =>
        Math.round(channel + (target[index] - channel) * amount)
            .toString(16)
            .padStart(2, '0'),
    );

    return `#${mixed.join('')}`;
};

/** A cor é livre por ambiente: aqui ela ganha um tom legível sobre as superfícies. */
const readable = (brand, target, surfaces) => {
    for (let amount = 0; amount < 1; amount += STEP) {
        const tone = mix(brand, target, amount);

        if (
            surfaces.every(
                (surface) => getContrastRatio(tone, surface) >= MINIMUM_CONTRAST,
            )
        ) {
            return tone;
        }
    }

    return target;
};

/** Neutros levemente tingidos pela marca dão unidade às superfícies. */
const scheme = (brand, accent, base) => {
    const surfaces = [base.background.default, base.background.paper];

    return {
        palette: {
            contrastThreshold: MINIMUM_CONTRAST,
            primary: { main: readable(brand, base.reach, surfaces) },
            secondary: { main: readable(accent, base.reach, surfaces) },
            background: base.background,
            text: base.text,
            divider: base.divider,
        },
    };
};

const light = (brand) => ({
    reach: '#000000',
    background: { default: mix('#f7f8fa', brand, 0.03), paper: '#ffffff' },
    text: {
        primary: mix('#16181d', brand, 0.08),
        secondary: mix('#565d6b', brand, 0.08),
    },
    divider: alpha('#16181d', 0.1),
});

const dark = (brand) => ({
    reach: '#ffffff',
    background: {
        default: mix('#0f1115', brand, 0.05),
        paper: mix('#171a20', brand, 0.06),
    },
    text: {
        primary: mix('#eceef2', brand, 0.04),
        secondary: mix('#a9b0bd', brand, 0.04),
    },
    divider: alpha('#ffffff', 0.1),
});

const components = {
    MuiCssBaseline: {
        styleOverrides: {
            body: { WebkitFontSmoothing: 'antialiased' },
            '@media (prefers-reduced-motion: reduce)': {
                '*, *::before, *::after': {
                    animationDuration: '0.01ms !important',
                    scrollBehavior: 'auto !important',
                    transitionDuration: '0.01ms !important',
                },
            },
            '@media print': {
                '[data-print="hide"]': { display: 'none !important' },
                '*': {
                    backgroundColor: 'transparent !important',
                    boxShadow: 'none !important',
                    color: '#000000 !important',
                },
            },
        },
    },
    MuiTextField: { defaultProps: { variant: 'standard' } },
    MuiButton: {
        defaultProps: { disableElevation: true },
        styleOverrides: { root: { textTransform: 'none' } },
    },
    MuiButtonBase: {
        styleOverrides: {
            root: ({ theme }) => ({
                '&.Mui-focusVisible': {
                    outline: `2px solid ${theme.vars.palette.primary.main}`,
                    outlineOffset: 2,
                },
            }),
        },
    },
    MuiInputBase: {
        styleOverrides: {
            root: ({ theme }) => ({
                // Abaixo de 16 px, o iOS amplia a tela ao focar o campo.
                [theme.breakpoints.down('sm')]: { fontSize: 16 },
            }),
        },
    },
    MuiPaper: {
        styleOverrides: {
            root: ({ theme, ownerState }) => {
                const border = `1px solid ${theme.vars.palette.divider}`;

                if (ownerState.variant !== 'elevation') {
                    return { backgroundImage: 'none' };
                }

                // Camadas com borda fina no lugar de sombra. O que flutua mantém a sombra.
                return ownerState.elevation === 1
                    ? { backgroundImage: 'none', boxShadow: 'none', border }
                    : {
                          backgroundImage: 'none',
                          ...theme.applyStyles('dark', { border }),
                      };
            },
        },
    },
    MuiTableCell: {
        styleOverrides: {
            head: ({ theme }) => ({
                color: theme.vars.palette.text.secondary,
                fontWeight: 600,
            }),
        },
    },
};

export function createAppTheme(branding = {}) {
    const brand = color(branding.primaryColor, DEFAULT_PRIMARY);
    const accent = color(branding.secondaryColor, DEFAULT_SECONDARY);

    return createTheme({
        cssVariables: { colorSchemeSelector: 'data-color-scheme' },
        colorSchemes: {
            light: scheme(brand, accent, light(brand)),
            dark: scheme(brand, accent, dark(brand)),
        },
        shape: { borderRadius: 12 },
        typography: {
            fontFamily: 'Roboto, Helvetica, Arial, sans-serif',
            h5: { fontWeight: 600, letterSpacing: '-0.01em' },
            h6: { fontWeight: 600 },
            button: { fontWeight: 500 },
        },
        components,
    });
}
