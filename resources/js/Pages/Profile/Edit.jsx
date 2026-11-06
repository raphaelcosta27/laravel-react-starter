import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import AddressesSection from './Partials/AddressesSection';
import ContactsSection from './Partials/ContactsSection';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdateAvatarForm from './Partials/UpdateAvatarForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
    personalData,
    genders,
    cpfRequired,
    avatarMaxKb,
    contacts,
    relationships,
    addresses,
    defaultCountry,
}) {
    const __ = useTranslations();

    return (
        <AuthenticatedLayout title={__('Profile')}>
            <Head title={__('Profile')} />

            <Stack spacing={3}>
                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        personalData={personalData}
                        genders={genders}
                        cpfRequired={cpfRequired}
                    />
                </Paper>

                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <UpdateAvatarForm maxKb={avatarMaxKb} />
                </Paper>

                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <ContactsSection
                        contacts={contacts}
                        relationships={relationships}
                    />
                </Paper>

                <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                    <AddressesSection
                        addresses={addresses}
                        defaultCountry={defaultCountry}
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
