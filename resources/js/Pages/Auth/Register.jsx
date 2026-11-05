import TextLink from '@/Components/TextLink';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function Register() {
    const __ = useTranslations();
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('Register')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Register')}
                </Typography>

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'name', { required: true })}
                        label={__('Name')}
                        autoComplete="name"
                        autoFocus
                    />

                    <TextField
                        {...fieldProps(form, 'email', { required: true })}
                        type="email"
                        label={__('Email')}
                        autoComplete="username"
                    />

                    <TextField
                        {...fieldProps(form, 'password', { required: true })}
                        type="password"
                        label={__('Password')}
                        autoComplete="new-password"
                    />

                    <TextField
                        {...fieldProps(form, 'password_confirmation', {
                            required: true,
                        })}
                        type="password"
                        label={__('Confirm Password')}
                        autoComplete="new-password"
                    />

                    <Stack
                        direction="row"
                        spacing={2}
                        sx={{ alignItems: 'center', justifyContent: 'flex-end' }}
                    >
                        <TextLink href={route('login')}>
                            {__('Already registered?')}
                        </TextLink>

                        <Button
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Register')}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
