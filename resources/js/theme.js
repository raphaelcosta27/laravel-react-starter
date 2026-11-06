import { createTheme } from '@mui/material/styles';

const DEFAULT_PRIMARY = '#3949ab';
const DEFAULT_SECONDARY = '#00695c';

const HEXADECIMAL = /^#[0-9a-f]{6}$/i;

const color = (value, fallback) =>
    HEXADECIMAL.test(value ?? '') ? value : fallback;

export function createAppTheme(branding = {}) {
    return createTheme({
        palette: {
            contrastThreshold: 4.5,
            primary: { main: color(branding.primaryColor, DEFAULT_PRIMARY) },
            secondary: {
                main: color(branding.secondaryColor, DEFAULT_SECONDARY),
            },
        },
        typography: {
            fontFamily: 'Roboto, Helvetica, Arial, sans-serif',
        },
        components: {
            MuiTextField: { defaultProps: { variant: 'standard' } },
        },
    });
}
