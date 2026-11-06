import TextLink from '@/Components/TextLink';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import SendOutlinedIcon from '@mui/icons-material/SendOutlined';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function RequestAccess({ status }) {
    const __ = useTranslations();
    const form = useForm({ email: '' });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('access.send'));
    };

    return (
        <GuestLayout>
            <Head title={__('Request new access')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Request new access')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__(
                        'If your temporary password has expired, enter your email to receive a new password and a new link.',
                    )}
                </Typography>

                {status && (
                    <Alert severity="success" role="status">
                        {status}
                    </Alert>
                )}

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'email', { kind: 'email' })}
                        type="email"
                        label={__('Email')}
                        autoComplete="username"
                        autoFocus
                    />

                    <Stack
                        direction="row"
                        sx={{ alignItems: 'center', justifyContent: 'space-between' }}
                    >
                        <TextLink href={route('login')}>{__('Log in')}</TextLink>

                        <Button
                            startIcon={<SendOutlinedIcon />}
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Send')}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
