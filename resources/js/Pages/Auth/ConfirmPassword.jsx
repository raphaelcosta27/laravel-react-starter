import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import CheckIcon from '@mui/icons-material/Check';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function ConfirmPassword() {
    const __ = useTranslations();
    const form = useForm({ password: '' });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('password.confirm'), {
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('Confirm Password')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Confirm Password')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__('This is a secure area of the application. Please confirm your password before continuing.')}
                </Typography>

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'password', { kind: 'password' })}
                        type="password"
                        label={__('Password')}
                        autoFocus
                    />

                    <Stack direction="row" sx={{ justifyContent: 'flex-end' }}>
                        <Button
                            startIcon={<CheckIcon />}
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Confirm')}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
