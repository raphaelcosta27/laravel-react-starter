import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function ForgotPassword({ status }) {
    const __ = useTranslations();
    const form = useForm({ email: '' });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title={__('Forgot Password')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Forgot Password')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.')}
                </Typography>

                {status && (
                    <Alert severity="success" role="status">
                        {status}
                    </Alert>
                )}

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'email')}
                        type="email"
                        label={__('Email')}
                        autoFocus
                    />

                    <Stack direction="row" sx={{ justifyContent: 'flex-end' }}>
                        <Button
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Email Password Reset Link')}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
