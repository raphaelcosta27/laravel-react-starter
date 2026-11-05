import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function ResetPassword({ token, email }) {
    const __ = useTranslations();
    const form = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('password.store'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('Reset Password')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Reset Password')}
                </Typography>

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'email')}
                        type="email"
                        label={__('Email')}
                        autoComplete="username"
                    />

                    <TextField
                        {...fieldProps(form, 'password')}
                        type="password"
                        label={__('Password')}
                        autoComplete="new-password"
                        autoFocus
                    />

                    <TextField
                        {...fieldProps(form, 'password_confirmation')}
                        type="password"
                        label={__('Confirm Password')}
                        autoComplete="new-password"
                    />

                    <Stack direction="row" sx={{ justifyContent: 'flex-end' }}>
                        <Button
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Reset Password')}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
