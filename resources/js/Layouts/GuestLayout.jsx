import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import Box from '@mui/material/Box';
import Paper from '@mui/material/Paper';

export default function GuestLayout({ children }) {
    return (
        <Box
            sx={{
                alignItems: 'center',
                bgcolor: 'grey.100',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: { sm: 'center' },
                minHeight: '100vh',
                pt: { xs: 3, sm: 0 },
            }}
        >
            <Link href="/">
                <ApplicationLogo style={{ height: 80, width: 80 }} />
            </Link>

            <Paper
                component="main"
                sx={{ maxWidth: { sm: 448 }, mt: 3, px: 3, py: 2, width: '100%' }}
            >
                {children}
            </Paper>
        </Box>
    );
}
