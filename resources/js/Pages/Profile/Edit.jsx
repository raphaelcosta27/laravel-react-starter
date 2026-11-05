import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    const __ = useTranslations();

    return (
        <AuthenticatedLayout title={__('Profile')}>
            <Head title={__('Profile')} />

            <Stack spacing={3}>
                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                    />
                </Paper>

                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <UpdatePasswordForm />
                </Paper>

                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <DeleteUserForm />
                </Paper>
            </Stack>
        </AuthenticatedLayout>
    );
}
