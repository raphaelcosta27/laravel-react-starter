import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import Paper from '@mui/material/Paper';
import Typography from '@mui/material/Typography';

export default function Dashboard() {
    const __ = useTranslations();

    return (
        <AuthenticatedLayout title={__('Dashboard')}>
            <Head title={__('Dashboard')} />

            <Paper sx={{ p: 3 }}>
                <Typography>{__("You're logged in!")}</Typography>
            </Paper>
        </AuthenticatedLayout>
    );
}
