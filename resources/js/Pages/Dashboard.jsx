import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, usePage } from '@inertiajs/react';
import Paper from '@mui/material/Paper';
import Typography from '@mui/material/Typography';

export default function Dashboard() {
    const __ = useTranslations();
    const { displayName } = usePage().props.auth;

    return (
        <AuthenticatedLayout title={__('Dashboard')}>
            <Head title={__('Dashboard')} />

            <Paper sx={{ p: 3 }}>
                <Typography component="h2" variant="h6">
                    {__('Hello, :name', { name: displayName })}
                </Typography>

                <Typography>{__("You're logged in!")}</Typography>
            </Paper>
        </AuthenticatedLayout>
    );
}
