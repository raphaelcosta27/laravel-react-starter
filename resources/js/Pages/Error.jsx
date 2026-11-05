import PostLink from '@/Components/PostLink';
import TextLink from '@/Components/TextLink';
import { useTranslations } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

const messages = {
    403: ['Access denied', 'You do not have permission to open this page.'],
    404: ['Page not found', 'The page you are looking for does not exist.'],
    419: ['Session expired', 'Reload the page and try again.'],
    429: ['Too many attempts', 'Wait a moment and try again.'],
    500: [
        'Something went wrong',
        'We could not complete your request. Try again in a few moments.',
    ],
    503: [
        'Service unavailable',
        'The application is temporarily unavailable. Try again soon.',
    ],
};

export default function Error({ status, message, canLogout = false }) {
    const __ = useTranslations();
    const [title, description] = messages[status] ?? messages[500];

    return (
        <>
            <Head title={__(title)} />

            <Stack
                component="main"
                spacing={1}
                sx={{
                    alignItems: 'center',
                    bgcolor: 'grey.100',
                    justifyContent: 'center',
                    minHeight: '100vh',
                    p: 3,
                    textAlign: 'center',
                }}
            >
                <Typography variant="h6" component="p" color="text.secondary">
                    {status}
                </Typography>
                <Typography component="h1" variant="h4">
                    {__(title)}
                </Typography>
                <Typography color="text.secondary">
                    {message ?? __(description)}
                </Typography>

                <Stack direction="row" spacing={2} sx={{ alignItems: 'center', pt: 2 }}>
                    <TextLink href="/">{__('Back to home page')}</TextLink>

                    {canLogout && (
                        <Button component={PostLink} href={route('logout')}>
                            {__('Log Out')}
                        </Button>
                    )}
                </Stack>
            </Stack>
        </>
    );
}
