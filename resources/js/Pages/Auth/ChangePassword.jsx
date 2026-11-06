import PostLink from '@/Components/PostLink';
import PasswordStrength from '@/Components/PasswordStrength';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function ChangePassword() {
    const __ = useTranslations();
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.put(route('password.update'), { onError: () => form.reset() });
    };

    return (
        <GuestLayout>
            <Head title={__('Change your password')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Change your password')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__(
                        'For your security, you need to set a new password before continuing.',
                    )}
                </Typography>

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'current_password')}
                        type="password"
                        label={__('Current Password')}
                        autoComplete="current-password"
                    />

                    <TextField
                        {...fieldProps(form, 'password')}
                        type="password"
                        label={__('New Password')}
                        autoComplete="new-password"
                    />

                    <PasswordStrength value={form.data.password} />

                    <TextField
                        {...fieldProps(form, 'password_confirmation')}
                        type="password"
                        label={__('Confirm Password')}
                        autoComplete="new-password"
                    />

                    <Stack
                        direction="row"
                        sx={{ alignItems: 'center', justifyContent: 'space-between' }}
                    >
                        <Button component={PostLink} href={route('logout')}>
                            {__('Log Out')}
                        </Button>

                        <Button
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Save')}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
