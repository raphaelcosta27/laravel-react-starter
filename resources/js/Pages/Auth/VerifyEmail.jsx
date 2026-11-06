import PostLink from '@/Components/PostLink';
import GuestLayout from '@/Layouts/GuestLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import ForwardToInboxOutlinedIcon from '@mui/icons-material/ForwardToInboxOutlined';
import LogoutIcon from '@mui/icons-material/Logout';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

export default function VerifyEmail({ status }) {
    const __ = useTranslations();
    const form = useForm({});

    const submit = (event) => {
        event.preventDefault();

        form.post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title={__('Email Verification')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Email Verification')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__("Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.")}
                </Typography>

                {status === 'verification-link-expired' && (
                    <Alert severity="error" role="status">
                        {__('This link has expired. Request a new one below.')}
                    </Alert>
                )}

                {status === 'verification-link-sent' && (
                    <Alert severity="success" role="status">
                        {__('A new verification link has been sent to the email address you provided during registration.')}
                    </Alert>
                )}

                <Stack
                    component="form"
                    direction="row"
                    sx={{ alignItems: 'center', justifyContent: 'space-between' }}
                    onSubmit={submit}
                >
                    <Button
                        startIcon={<ForwardToInboxOutlinedIcon />}
                        type="submit"
                        variant="contained"
                        disabled={form.processing}
                    >
                        {__('Resend Verification Email')}
                    </Button>

                    <Button startIcon={<LogoutIcon />} component={PostLink} href={route('logout')}>
                        {__('Log Out')}
                    </Button>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
