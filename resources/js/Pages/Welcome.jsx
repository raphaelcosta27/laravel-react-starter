import ApplicationLogo from '@/Components/ApplicationLogo';
import TextLink from '@/Components/TextLink';
import { useTranslations } from '@/lib/i18n';
import { Head, usePage } from '@inertiajs/react';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

export default function Welcome({ canLogin, canRegister, status }) {
    const __ = useTranslations();
    const { appName, auth } = usePage().props;
    const checkedAt = new Date(status.checkedAt).toLocaleString(
        document.documentElement.lang || undefined,
    );

    return (
        <>
            <Head title={appName} />

            <Stack
                component="main"
                spacing={3}
                sx={{
                    alignItems: 'center',
                    bgcolor: 'grey.100',
                    justifyContent: 'center',
                    minHeight: '100vh',
                    p: 3,
                    textAlign: 'center',
                }}
            >
                <ApplicationLogo style={{ height: 80, width: 80 }} />

                <Typography component="h1" variant="h4">
                    {appName}
                </Typography>

                <Paper
                    component="section"
                    aria-labelledby="application-status"
                    sx={{ px: 3, py: 2 }}
                >
                    <Typography
                        id="application-status"
                        component="h2"
                        variant="subtitle1"
                    >
                        {__('Application status')}
                    </Typography>
                    <Typography color="text.secondary">
                        {__(
                            status.database
                                ? 'Database connected'
                                : 'Database unavailable',
                        )}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        {__('Checked at')}{' '}
                        <time dateTime={status.checkedAt}>{checkedAt}</time>
                    </Typography>
                </Paper>

                <Stack
                    component="nav"
                    aria-label={appName}
                    direction="row"
                    spacing={2}
                >
                    {auth.user ? (
                        <TextLink href={route('dashboard')}>
                            {__('Dashboard')}
                        </TextLink>
                    ) : (
                        <>
                            {canLogin && (
                                <TextLink href={route('login')}>
                                    {__('Log in')}
                                </TextLink>
                            )}
                            {canRegister && (
                                <TextLink href={route('register')}>
                                    {__('Register')}
                                </TextLink>
                            )}
                        </>
                    )}
                </Stack>
            </Stack>
        </>
    );
}
