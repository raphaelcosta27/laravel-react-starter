import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';
import Box from '@mui/material/Box';
import Paper from '@mui/material/Paper';
import Typography from '@mui/material/Typography';

/** A área de marca aparece a partir de 900 px: no celular, só o formulário. */
function BrandArea() {
    const { appName, branding } = usePage().props;

    return (
        <Box
            component="aside"
            aria-label={appName}
            sx={(theme) => ({
                bgcolor: 'primary.main',
                backgroundImage:
                    'radial-gradient(circle at 85% 12%, rgba(255, 255, 255, 0.16), transparent 42%)',
                color: 'primary.contrastText',
                display: { xs: 'none', md: 'flex' },
                flex: '0 0 42%',
                flexDirection: 'column',
                gap: 4,
                justifyContent: 'space-between',
                minWidth: 0,
                p: 6,
                // A cor cheia ofuscaria no escuro: lá a área fica como superfície.
                ...theme.applyStyles('dark', {
                    bgcolor: 'background.paper',
                    backgroundImage: 'none',
                    borderInlineEnd: 1,
                    borderColor: 'divider',
                    color: 'text.primary',
                }),
            })}
        >
            <Box sx={{ alignItems: 'center', display: 'flex', gap: 1.5 }}>
                <ApplicationLogo
                    alt=""
                    style={{ display: 'block', height: 40, width: 40 }}
                />

                <Typography noWrap variant="h6" component="p">
                    {appName}
                </Typography>
            </Box>

            {branding.tagline && (
                <Typography
                    variant="h4"
                    component="p"
                    sx={{ fontWeight: 600, maxWidth: 480 }}
                >
                    {branding.tagline}
                </Typography>
            )}
        </Box>
    );
}

export default function GuestLayout({ children }) {
    return (
        <Box
            sx={{
                bgcolor: 'background.default',
                display: 'flex',
                minHeight: '100dvh',
            }}
        >
            <BrandArea />

            <Box
                sx={{
                    alignItems: 'center',
                    display: 'flex',
                    flexDirection: 'column',
                    flexGrow: 1,
                    gap: 3,
                    justifyContent: { sm: 'center' },
                    minWidth: 0,
                    px: 2,
                    py: 3,
                }}
            >
                <Box
                    component={Link}
                    href="/"
                    sx={{ display: { md: 'none' }, lineHeight: 0 }}
                >
                    <ApplicationLogo style={{ height: 64, width: 64 }} />
                </Box>

                <Paper
                    component="main"
                    sx={{ maxWidth: 448, px: 3, py: 3, width: '100%' }}
                >
                    {children}
                </Paper>
            </Box>
        </Box>
    );
}
